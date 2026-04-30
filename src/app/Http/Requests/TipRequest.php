<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * 팁 글 작성/수정 요청에서 공통으로 사용하는 베이스 FormRequest
 * 
 * [역할]
 * - 작성/수정 화면에서 들어오는 입력값의 공통 검증 규칙 정의
 * - 컨트롤러에서 바로 쓰기 쉽게 요청 데이터를 목적별로 가공
 * - 파일/태그/draft key/submit source 같은 부가 입력을 분리 
 * 
 */
abstract class TipRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'thumbnail' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived,deleted'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'thumbnail_delete' => ['nullable', 'boolean'],
            'editor_draft_key' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'tags' => ['nullable', 'string'],
            'submit_from' => ['nullable', 'in:admin,front'],
        ];
    }

    /**
     * 실제 Tip 모델 저장/수정에 바로 사용할 수 있는 payload만 반환
     * 
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return Arr::except($this->validated(), [
            'thumbnail', // 파일 객체라 모델 속성에 그대로 넣으면x
            'thumbnail_delete', // DB 컬럼이 아니라 제어용 플래그
            'editor_draft_key', // draft 이미지 이동용 키이지 tips 테이블 컬럼이 아님
            'tags', // tags는 별도 sync 로직으로 처리하므로 직접 mass assignment 하지 않음
            'submit_from', // redirect 분기용 메타 정보일 뿐 DB 저장 대상이 아님
        ]);
    }

    /**
     * 업로드된 썸네일 파일 객체를 안전하게 반환
     * : 컨트롤러/서비스에서 request->file('thumbnail')를 직접 매번 다루지 않도록 
     * (서비스 계층은 "파일이 있나 없나"만 보게 단순화)
     */
    public function thumbnailFile(): ?UploadedFile
    {
        $file = $this->file('thumbnail');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * 태그 원본 payload를 반환 
     * : 현재 tags는 배열이 아니라 문자열(json 형태)로 들어오기 때문에
     * 그대로 service로 넘겨서 TipService::syncTipTagsFromPayload()가 해결하도록
     * 
     * - 반환 규칙
     *  - 요청에 tags필드가 존재하면 문자열 반환
     *  - 아예 없으면 null 반환
     */
    public function tagsPayload(): ?string
    {
        return $this->has('tags')
            ? (string) $this->input('tags', '')
            : null;
    }

    /**
     * 에디터 draft key를 반환
     * 
     * [사용목적]
     * - 작성 중 업로드된 에디터 이미지는 임시 draft 경로에 저장됨
     * - 글 저장 완료 후 이 key를 기준으로 실제 게시글 경로로 이동시킴
     */
    public function draftKey(): ?string
    {
        $draftKey = (string) $this->input('editor_draft_key', '');

        return $draftKey !== '' ? $draftKey : null;
    }

    /**
     * 요청이 어느 화면에서 들어왔는지 반환 (admin | front)
     * 
     * [목적]
     * - 처리 후 redirect 위치를 분기
     * - 관리자 화면으로 보낼 지, 상세 화면/홈으로 보낼지 결정 
     */
    public function submitFrom(): string
    {
        return (string) $this->input('submit_from', '');
    }

    /**
     * 썸네일 삭제 요청 여부를 판별
     * (update 로직에서 기존 썸네일을 null로 바꿀지 판단하는 데 사용됨)
     */
    public function shouldDeleteThumbnail(): bool
    {
        return $this->boolean('thumbnail_delete') && $this->thumbnailFile() === null;
    }
}
