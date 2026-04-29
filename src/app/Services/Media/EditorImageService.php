<?php

namespace App\Services\Media;

use App\Models\Tip;
use App\Models\User;
use InvalidArgumentException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

/**
 * 에디터 이미지의 저장, 이동, 삭제, URL 변환
 * 
 * [역할]
 * 1. Summernnote 등 에디터에서 업로드한 이미지를 저장
 * 2. 새 글 작성 중 임시 저장된 draft 이미지를 실제 게시글 경로로 이동
 * 3. 게시글 수정 시 본문에서 제거된 이미지를 스토리지에서도 삭제
 * 4. 게시글 삭제 시 해당 게시글에 연결된 모든 에디터 이미지를 삭제 
 * 5. 이미지 경로를 실제 접근 가능한 URL로 변환 
 */
class EditorImageService
{
    public function __construct(
        private R2ImageStorageService $storage,
    ) {
    }

    /**
     * 에디터 이미지 저장
     * 
     * @param User $actor : 현재 이미지를 업로드하는 사용자
     * @param UploadedFile $file : 사용자가 업로드한 실제 이미지 파일
     * @param Tip|null $tip : 이미지가 연결될 게시글 
     * @param string|null $filename : 원본 파일명을 기반으로 만든 이름
     * 
     * @return string : 저장된 파일의 내부 경로
     *  ex) posts/15/editor/example.webp
     *  ex) drafts/users/3/draft-abc123/post-editor/example.webp
     *
     * [흐름]
     * 1. $tip 존재여부
     *  - 있으면 -> 게시글 전용 이미지 경로 만들기
     *  - 없으면 -> 사용자별 draft 이미지 경로 만들기 
     * 2. R2ImageStorageService를 통해 실제 파일을 저장
     * 3. 저장된 파일 경로 반환
     */
    public function store(
        User $actor,
        UploadedFile $file,
        ?Tip $tip = null,
        ?string $filename = null,
        ?string $draftKey = null,
    ): string
    {
        // 저장 경로 prefix 결정 
        $prefix = $tip !== null
            ? $this->prefixForTip($actor, $tip)
            : $this->draftPrefixFor($actor, $draftKey);

        return $this->storage->store($file, $prefix, $filename);
    }

    /**
     * 저장된 내부 파일 경로를 실제 접근 가능한 URL로 변환
     * 
     * [사용]
     * - 이미지 업로드 후 JSON 응답으로 이미지 URL을 내려줄 때
     * - 본문 안의 draft 이미지 URL을 실제 게시글 이미지 URL로 교체할 때 
     * 
     * @param string $path : 스토리지 내부 파일 경로
     * @return string : 브라우저에서 접근 가능한 이미지 URL 
     */
    public function url(string $path): string
    {
        return $this->storage->url($path);
    }

    /**
     * draft 경로에 임시 저장된 이미지를 실제 게시글 경로로 이동하는 메서드
     * 
     * @param User $actor : 현재 작업을 수행하는 사용자
     * @param Tip $tip : 이미지가 최종적으로 연결될 게시글
     * @param string $content : 게시글 본문 HTML
     * 
     * @return string : draft 이미지 URL이 실제 게시글 이미지 URL로 교체된 본문 HTML
     * 
     * [사용시점]
     * - 새 글 작성 완료 후 Tip이 실제로 생성되었을 때 
     * - 작성 중에는 tip_id가 없어서 이미지를 draft 경로에 저장한다
     * - 글 저장 후에는 게시글 id가 생기므로 draft 이미지를 게시글 전용 경로로 옮김
     */
    public function relocateDraftImages(User $actor, Tip $tip, string $content, ?string $draftKey = null): string
    {
        if (blank($draftKey)) {
            return $content;
        }

        // 최종 저장될 게시글 전용 이미지 prefix  
        $targetPrefix = $this->prefixForTip($actor, $tip);
        // 본문 문자열 치환에 사용할 배열 
        $replacements = [];
            // 구조 : ['기존 draft 이미지 URL' => '새 게시글 이미지 URL' ]
        // 이동 성공한 파일 목록 (중간에 오류가 발생했을 때 롤백하기 위해 사용 )
        $movedPaths = [];
        // 현재 사용자의 draft 이미지 prefix (새 글 작성 중 업로드한 이미지는 이 경로 아래에 들어감)
        $draftPrefix = $this->draftPrefixFor($actor, $draftKey);

        try {
            // 본문 html 에서 모든 <img src="..."> 값을 추출
            foreach ($this->extractImageSources($content) as $source) {
                // 현재 img src가 draft 경로에 해당하는지 확인 (ex. drafts/users/3/post-editor/a.webp)
                $draftPath = $this->matchStoredPath($source, $draftPrefix);

                if ($draftPath === null || array_key_exists($source, $replacements)) {
                    continue;
                }

                // 이동될 최종 파일 경로 생성 
                $targetPath = sprintf('%s/%s', $targetPrefix, basename($draftPath));


                if ($this->storage->exists($draftPath)) {
                    if ($this->storage->exists($targetPath)) { // 이미 target 경로에 같은 파일명이 존재 
                        $this->storage->delete($draftPath); // target 파일이 이미 있으면 draft 파일만 삭제 (기존 target 파일은 유지)
                    } else { // 파일이 없으면 draft 파일을 target 경로로 이동 
                        $this->storage->move($draftPath, $targetPath);
                        $movedPaths[$draftPath] = $targetPath; // 이동 성공 기록
                    }
                } elseif (! $this->storage->exists($targetPath)) { // 교체 할 수 없는 파일이므로 스킵
                    continue;
                }

                // 본문 치환 목록에 추가 (기존 draft 이미지 URL을 새 게시글 이미지 URL로 교체하기 위한 데이터)
                $replacements[$source] = $this->storage->url($targetPath);
            }

            // draft 디렉터리 정리 (현재 사용자의 draft 이미지 디렉터리에 남은 파일들을 삭제)
            $this->cleanupDraftDirectory($actor, $draftKey);
        } catch (\Throwable $e) {
            foreach (array_reverse($movedPaths, true) as $draftPath => $targetPath) {
                try {
                    if ($this->storage->exists($targetPath)) {
                        $this->storage->move($targetPath, $draftPath);
                    }
                } catch (\Throwable) {
                }
            }

            throw $e;
        }

        // 교체할 이미지 URL이 하나도 없으면 -> 원본 content를 그대로 반환 
        if ($replacements === []) {
            return $content;
        }

        // 본문 안의 draft 이미지 url을 실제 게시글 이미지 url로 교체한다
        return strtr($content, $replacements);
    }

    /**
     * 게시글 수정 시 본문에서 제거된 이미지를 스토리지에서도 삭제
     * 
     * @param User $actor : 현재 수정 작업을 하는 사용자
     * @param Tip $tip : 수정 대상 게시글 
     * @param string $previeousContent : 수정 전 본문 HTML 
     * @param string $currentContent : 수정 후 본문 HTML 
     * 
     * [사용시점]
     * - 게시글 수정 저장 시
     * - 수정 전 본문과 수정 후 본문을 비교
     * - 더 이상 사용하지 않는 이미지를 삭제
     */
    public function deleteRemovedTipImages(User $actor, Tip $tip, string $previousContent, string $currentContent): void
    {
        // 해당 게시글의 이미지 prefix 구하기
        $prefix = $this->prefixForTip($actor, $tip);
        // 수정 전 본문에 포함된 이미지 경로 목록(해당 게시글 prefix에 해당하는 이미지만 추출)
        $previousPaths = $this->contentPathsForPrefix($previousContent, $prefix);
        // 수정 후 본문에 포함된 이미지 경로 목록
        $currentPaths = $this->contentPathsForPrefix($currentContent, $prefix);

        // 수정 전에는 있었지만 수정 후에는 없는 이미지 삭제 
        foreach (array_diff($previousPaths, $currentPaths) as $path) {
            $this->storage->delete($path);
        }
    }

    // 특정 게시글에 연결된 모든 에디터 이미지를 삭제
    public function deleteAllTipImages(User $actor, Tip $tip): void
    {
        foreach ($this->storage->files($this->prefixForTip($actor, $tip)) as $path) {
            $this->storage->delete($path);
        }
    }

    /**
     * 특정 Tip의 에디터 이미지 저장 prefix를 반환 (+권한 검사)
     * 
     * @param User $actor : 현재 작업을 수행하는 사용자
     * @param Tip $tip : 대상 게시글
     * 
     * @return string : 게시글 전용 에디터 이미지 prefix
     * 
     * @throws AuthorizationException : 권한이 없는 사용자가 다른 사람의 tip 이미지를 조작하려고 할 때 발생 
     * 
     * [권한 규칙]
     * - 관리자는 모든 Tip의 이미지 작업 가능
     * - 일반 사용자는 본인이 작성한 Tip에 대해서만 이미지 작업 가능
     */
    private function prefixForTip(User $actor, Tip $tip): string
    {
        // 권한검사 
        if (! $actor->isAdmin() && (int) $actor->id !== (int) $tip->user_id) {
            throw new AuthorizationException('이 팁의 에디터 이미지를 업로드할 권한이 없습니다.');
        }

        // 게시글 전용 에디터 이미지 경로 반환 
        return MediaPath::postEditor($tip->id);
    }

    /**
     * 특정 Tip의 에디터 이미지 저장 prefix를 반환
     */
    private function draftPrefixFor(User $actor, ?string $draftKey): string
    {
        $draftKey = trim((string) $draftKey);

        if ($draftKey === '') {
            throw new InvalidArgumentException('에디터 draft key가 비어 있습니다.');
        }

        if (! preg_match('/\A[a-zA-Z0-9_-]+\z/', $draftKey)) {
            throw new InvalidArgumentException('에디터 draft key 형식이 올바르지 않습니다.');
        }

        return MediaPath::draftPostEditor($actor->id, $draftKey);
    }

    /**
     * HTML 본문에서 모든 이미지 src 값을 추출
     * 
     * @param string $content : HTML 본문
     * 
     * @return array : 본문 안의 이미지 src 목록 
     * 
     * [사용시점]
     * - 본문 안에 어떤 이미지들이 들어있는지 확인
     * - draft 이미지를 실제 게시글 경로로 이동
     * - 수정 전/후 본문 이미지 차이 비교
     */
    private function extractImageSources(string $content): array
    {
        preg_match_all('/<img\b[^>]*\bsrc=(["\'])(.*?)\1/i', $content, $matches);

        return array_values(array_unique($matches[2] ?? []));
    }

    /**
     * HTML 본문에서 특정 prefix에 해당하는 이미지 경로만 추출
     * 
     * @param string $content : HTML 본문
     * @param string $prefix : 찾고 싶은 이미지 경로 prefix
     * 
     * @return array : 특정 prefix에 해당하는 스토리지 내부 이미지 경로 목록 
     * 
     * [사용지점]
     * - 게시글 수정 전/후 이미지를 비교할 때 
     * - 해당 게시글 이미지 경로만 골라날 때 
     */
    private function contentPathsForPrefix(string $content, string $prefix): array
    {
        $paths = [];

        /**
         * 본문에서 모든 img src를 추출한 뒤,
         * 그중 특정 prefix에 해당하는 경로만 골라냄. 
         */
        foreach ($this->extractImageSources($content) as $source) {
            $path = $this->matchStoredPath($source, $prefix); // source가 특정 prefix에 해당하면 내부 스토리지 경로르 반환 (해당하지 않으면 null 반환)

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * 이미지 src에서 특정 prefix에 해당하는 스토리지 내부 경로를 추출하는 메서드 (본문 img src는 여러 형태일 수 있기 때문)
     * 
     * @param string $source : img 태그의 src값
     * @param string $prefix : 찾고 싶은 스토리지 경로 prefix
     * 
     * @return string|null : 매칭되면 내부 스토리지 경로 반환 | 매칭되지 않으면 null 반환 
     */
    private function matchStoredPath(string $source, string $prefix): ?string
    {
        // source가 비어있으면 처리 할 수 없으므로 null 반환 
        if (blank($source)) {
            return null;
        }

        /**
         * 찾을 기준 문자열 생성 
         */
        $needle = $prefix . '/'; // 유사 경로 잘못 매칭되는 것 줄이기 위함
        $directPath = ltrim($source, '/'); // 앞쪽의 / 제거
        $position = strpos($directPath, $needle); // source 문자열 안에 prefix가 직접 포함되어 있는지 확인 (source가 이미 상대 경로거나 내부 경로인 경우를 처리)


        if ($position !== false) {
            return substr($directPath, $position);
        }

        // source가 전체 URL인 경우 path 부분만 추출 
        $parsedPath = parse_url($source, PHP_URL_PATH);

        if (! is_string($parsedPath)) {
            return null;
        }

        // path 앞쪽의 / 제거 
        $normalizedPath = ltrim($parsedPath, '/');

        // url path 안에서 prefix가 있는지 다시 확인 
        $position = strpos($normalizedPath, $needle);

        if ($position === false) {
            return null;
        }

        return substr($normalizedPath, $position);
    }

    /**
     * 현재 사용자의 draft 에디터 이미지 디렉터리를 비움
     * 
     * @param User $actor : 현재 사용자
     * @return void 
     * 
     * [사용시점]
     * - 새 글 저장 후 draft 이미지를 실제 게시글 경로로 이동한 다음, 남아 있는 임시 파일을 정리
     */
    private function cleanupDraftDirectory(User $actor, string $draftKey): void
    {
        foreach ($this->storage->files($this->draftPrefixFor($actor, $draftKey)) as $path) {
            $this->storage->delete($path);
        }
    }
}
