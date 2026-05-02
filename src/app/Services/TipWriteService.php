<?php

namespace App\Services;

use App\Models\Tip;
use App\Models\User;
use App\Services\Media\EditorImageService;
use App\Services\Tip\TipTagService;
use App\Services\Media\TipThumbnailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 팁 글 작성/수정/삭제의 실제 비지니스 로직 담당 
 * 
 * [역할]
 * - Tip 생성/수정/삭제
 * - 태그 동기화
 * - 썸네일 저장/교체/삭제
 * - 에디터 draft 이미지 추처리
 * - 수정 시 제거된 본문 이미지 정리
 * - 삭제 후 의도된 미디어 정리 
 */
class TipWriteService
{
    public function __construct(
        private TipTagService $tipTagService, // 태그 정리/차단 정책/sync 담당
        private EditorImageService $editorImages, // 본문에디터 이미지 
        private TipThumbnailService $tipThumbnails, // 썸네일 저장/삭제 
    ) {
    }

    /**
     * 새 팁 글 생성
     * 
     * [처리흐름]
     * 1. transaction 시작
     * 2. Tip 생성
     * 3. 썸네일이 있으면 저장 후 경로 반영
     * 4. 태그 payload가 있으면 태그 동기화
     * 5. commit
     * 6. commit 후 draft editor 이미지를 실제 게시글 경로로 이동
     * 7. TipWriteResult 반환 
     * 
     * @param User $actor 현재 작업 사용자
     * @param array<string, mixed> $attributes Tip 모델에 반영할 본문 데이터
     * @param UploadedFile|null $thumbnailFile 업로드된 썸네일 파일
     * @param string|null $tagsPayload 프론트에서 넘어온 태그 문자열(JSON)
     * @param string|null $draftKey 에디터 임시 이미지 식별 key
     */
    public function create(
        User $actor,
        array $attributes,
        ?UploadedFile $thumbnailFile = null,
        ?string $tagsPayload = null,
        ?string $draftKey = null,
    ): TipWriteResult {
        // 생성된 Tip 모델
        $tip = null;
        // 새로 저장한 썸네일 경로 (중간 실패 시 보정 삭제에 사용)
        $storedThumbnailPath = null;
        // 저장은 성공했지만 사용자에게 보여줄 경고 메세지
        $warningMessage = null;

        try {
            DB::transaction(function () use (
                $actor,
                $attributes,
                $thumbnailFile,
                $tagsPayload,
                $draftKey,
                &$tip,
                &$storedThumbnailPath,
                &$warningMessage,
            ): void {
                // 실제 Tip 레코드 생성 (요청 사용자의 id를 강제로 주입)
                $tip = Tip::create(array_merge($attributes, [
                    'user_id' => $actor->id,
                ]));

                // 썸네일 파일이 있으면 저장 후 Tip.thumbnail 컬럼에 반영
                if ($thumbnailFile !== null) {
                    $storedThumbnailPath = $this->tipThumbnails->store($tip, $thumbnailFile);
                    $tip->thumbnail = $storedThumbnailPath;
                    $tip->save();
                }

                // 태그 데이터가 넘어온 경우, 태그 sync 수행 
                // 차단된 태그가 있으면, 경고 메세지를 반환받아 저장 
                $warningMessage = $this->syncTagsIfProvided($tip, $tagsPayload);

                // draft 이미지 이동 
                DB::afterCommit(function () use ($actor, $tip, $draftKey): void {
                    $this->syncEditorDraftImages($actor, $tip, $draftKey);
                });
            });
        } catch (Throwable $e) {
             // 썸네일이 이미 저장된 상태에서 실패한 경우, orphan 파일이 
             // 남지 않도록 저장한 새 썸네일 삭제 시도. 
            if ($storedThumbnailPath !== null) {
                try {
                    $this->tipThumbnails->deletePath($storedThumbnailPath);
                } catch (Throwable) {
                }
            }

            throw $e;
        }

        // transaction 종료 후에도 Tip이 채워지지 않았다면 비정상 상태 
        if (! $tip instanceof Tip) {
            throw new RuntimeException('팁 생성 결과를 확인할 수 없습니다.');
        }

        // 컨트롤러가 redirect와 flash 메세지 처리를 할 수 있도록 결과 객체로 반환
        return new TipWriteResult($tip, $warningMessage);
    }

    /**
     * 기존 팁 글 수정
     * 
     * [처리흐름]
     * 1. 수정 전 본문/기존 썸네일 경로 기억
     * 2. transaction 시작
     * 3. 수정 데이터 구성
     * 4. 새 썸네일 있으면 저장
     * 5. 썸네일 삭제 요청이면 thumbnail을 null  처리
     * 6. 태그 sync
     * 7. Tip update
     * 8. commit
     * 9. commit 후
     *  - draft 이미지 이동
     *  - 본문에서 제거된 이미지 삭제
     *  - 교체 전 썸네일 삭제
     */
    public function update(
        User $actor,
        Tip $tip,
        array $attributes,
        ?UploadedFile $thumbnailFile = null,
        bool $deleteThumbnail = false,
        ?string $tagsPayload = null,
        ?string $draftKey = null,
    ): TipWriteResult {
        // 수정 전 본문 (수정 후 제거된 이미지를 계산할 때 비교 기준으로 사용)
        $previousContent = (string) $tip->content;
        // 기존 썸네일 경로 (수정 성공 후 이전 파일 정리에 사용)
        $oldThumbnailPath = $tip->thumbnail;
        // 새로 저장한 썸네일 경로 (실패 시 롤백 보정 삭제에 사용)
        $newThumbnailPath = null;
        // 수정 과정에서 사용자에게 안내할 경고 메세지
        $warningMessage = null;

        try {
            DB::transaction(function () use (
                $actor,
                $tip,
                $attributes,
                $thumbnailFile,
                $deleteThumbnail,
                $tagsPayload,
                $draftKey,
                $previousContent,
                $oldThumbnailPath,
                &$newThumbnailPath,
                &$warningMessage,
            ): void {
                // 수정자 정보를 강제로 반영
                $updateAttributes = array_merge($attributes, [
                    'update_user_id' => $actor->id,
                ]);

                // 새 썸네일 업로드가 있으면 먼저 저장하고, 저장된 경로를 수정 데이터에 반영
                if ($thumbnailFile !== null) {
                    $newThumbnailPath = $this->tipThumbnails->store($tip, $thumbnailFile);
                    $updateAttributes['thumbnail'] = $newThumbnailPath;
                }
                
                // 기존 썸네일 삭제 요청이면 DB 컬럼을 NULL 처리 
                if ($deleteThumbnail) {
                    $updateAttributes['thumbnail'] = null;
                }

                // 태그 payload가 있으면 현재 글 기준으로 태그 sync 수행
                $warningMessage = $this->syncTagsIfProvided($tip, $tagsPayload);

                // 최종 수정 반영 
                $tip->update($updateAttributes);

                // 수정 성공 후에만 수행해야 하는 파일 정리 작업을 예약 
                DB::afterCommit(function () use (
                    $actor,
                    $tip,
                    $draftKey,
                    $previousContent,
                    $oldThumbnailPath,
                    $newThumbnailPath,
                    $deleteThumbnail,
                ): void {
                    // draft editor 이미지를 실제 게시글 경로로 이동 
                    $this->syncEditorDraftImages($actor, $tip, $draftKey);
                    // 수정 전 본문에는 있었지만 수정 후에는 사라진 이미지 삭제
                    $this->cleanupRemovedEditorImages($actor, $tip, $previousContent);
                    // 새 썸네일로 교체했거나 삭제 요청이 있었다면, 
                    // 예전 썸네일 파일을 실제 스토리지에서 제거
                    if ($newThumbnailPath !== null || $deleteThumbnail) {
                        $this->tipThumbnails->deletePath($oldThumbnailPath);
                    }
                });
            });
        } catch (Throwable $e) {
            if ($newThumbnailPath !== null) {
                try {
                    $this->tipThumbnails->deletePath($newThumbnailPath);
                } catch (Throwable) {
                }
            }

            throw $e;
        }

        if (! $tip instanceof Tip) {
            throw new RuntimeException('팁 수정 결과를 확인할 수 없습니다.');
        }

        return new TipWriteResult($tip, $warningMessage);
    }

    /**
     * 팁 글 삭제 
     * 
     * [처리 흐름]
     * 1. 기존 썸네일 경로 기억
     * 2. trasaction 안에서 Tip 삭제
     * 3. comit 후 연결된 미디어 정리 
     */
    public function delete(User $actor, Tip $tip): void
    {
        // 삭제 후 정리할 썸네일 파일 경로 백업 
        $thumbnailPath = $tip->thumbnail;

        DB::transaction(function () use ($actor, $tip, $thumbnailPath): void {
            // tip 레코드 삭제 
            $tip->delete();

            // DB 삭제가 확정된 뒤에만 연결 미디어 정리 수행 
            DB::afterCommit(function () use ($actor, $tip, $thumbnailPath): void {
                $this->cleanupDeletedTipMedia($actor, $tip, $thumbnailPath);
            });
        });
    }

    private function syncTagsIfProvided(Tip $tip, ?string $tagsPayload): ?string
    {
        if ($tagsPayload === null) {
            return null;
        }

        return $this->tipTagService->syncTipTagsFromPayload($tip, $tagsPayload);
    }

    /**
     * draft 경로에 있는 에디터 이미지를 실제 게시글 경로로 이동하고, 
     * 본문 안 이미지 URL도 새 경로에 맞게 갱신 
     */
    private function syncEditorDraftImages(User $actor, Tip $tip, ?string $draftKey = null): void
    {
        if (blank($draftKey)) {
            return;
        }

        try {
            $relocatedContent = $this->editorImages->relocateDraftImages($actor, $tip, (string) $tip->content, $draftKey);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        if ($relocatedContent === $tip->content) {
            return;
        }

        $tip->content = $relocatedContent;
        $tip->save();
    }

    /**
     * 수정 전 본문과 수정 후 본문을 비교해서 
     * 더 이상 사용되지 않는 에디터 이미지를 스토리지에서 삭제 
     */
    private function cleanupRemovedEditorImages(User $actor, Tip $tip, string $previousContent): void
    {
        try {
            $this->editorImages->deleteRemovedTipImages($actor, $tip, $previousContent, (string) $tip->content);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * 글 삭제 후 남은 연결 미디어를 정리
     * 
     * 정리대상 : 본문 에디터 이미지 전체, 썸네일 파일 
     * 
     * 실패시)
     * - 각 작업별로 개별 report
     * - 이미 DB 삭제는 끝난 상태이므로 예외를 다시 던지지 않음 
     */
    private function cleanupDeletedTipMedia(User $actor, Tip $tip, ?string $thumbnailPath): void
    {
        try {
            $this->editorImages->deleteAllTipImages($actor, $tip);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $this->tipThumbnails->deletePath($thumbnailPath);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
