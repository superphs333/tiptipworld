<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Services\Media\EditorImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * 에디터 이미지 저장 담당
 */
class SummernoteController extends Controller
{
    public function __construct(
        private EditorImageService $editorImages,
    ) {
    }

    /**
     * Summernote 단일 이미지 업로드 처리
     *
     * @param Request $request
     * @return json{url, alt}
     * 
     * - 유효성 검증 : image + mime + 용량제한 
     * - 저장 디스크 : EditorImageService 사용
     * 
     * [흐름]
     * 1. 클라이언트에서 image 파일 + 선택적으로 tip_id전송
     * 2. validate() -> 이미지 형식, MIME타입, 용량, tip_id 존재 여부 검증
     * 3. 원본 파일명에서 확장자를 제거한 이름을 alt 텍스트로 사용할 값으로 추출
     * 4. tip_id가 있으면 해당 Tip 모델을 조회한다
     * 5. EditorImageService를 통해 이미지를 저장한다
     * 6. 저장된 경로를 다시 접근 가능한 URL로 변환한다
     * 7. Summernote가 본문에 이미지를 삽입할 수 있도록 JSON 형태로 url과 alt반환 
     * 
     * 응답 예시:
     * {
     *     "url": "https://cdn.example.com/editor-images/xxx.webp",
     *     "alt": "original-file-name"
     * }
     * 
     */
    public function uploadImage(Request $request): JsonResponse
    {
        // 요청 데이터 유효성 검증 
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
            'tip_id' => ['nullable', 'integer', 'exists:tips,id'],
            'draft_key' => ['nullable', 'required_without:tip_id', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $image = $validated['image']; 
        $tipId = $validated['tip_id'] ?? null; 
        $draftKey = $validated['draft_key'] ?? null;
        $filename = pathinfo((string) $image->getClientOriginalName(), PATHINFO_FILENAME);
        $tip = $tipId !== null ? Tip::findOrFail((int) $tipId) : null;

        try {
            $storedPath = $this->editorImages->store($request->user(), $image, $tip, $filename, $draftKey);
        } catch (RuntimeException) {
            return response()->json([
                'message' => '이미지 업로드에 실패했습니다.',
            ], 500);
        }

        return response()->json([
            'url' => $this->editorImages->url($storedPath),
            'alt' => $filename,
        ]);
    }
}
