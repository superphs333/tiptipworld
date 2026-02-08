<?php

namespace App\Http\Controllers;

use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SummernoteController extends Controller
{
    /**
     * Summernote 단일 이미지 업로드 처리
     *
     * @param Request $request
     * @return json{url, alt}
     * 
     * - 유효성 검증 : image + mime + 용량제한 
     * - 저장 디스크 : FileStorageService 설정값 사용
     * 
     */
    public function uploadImage(Request $request, FileStorageService $storage): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $image = $validated['image'];

        try {
            $storedPath = $storage->storeUploaded($image, 'post');
        } catch (RuntimeException) {
            return response()->json([
                'message' => '이미지 업로드에 실패했습니다.',
            ], 500);
        }

        return response()->json([
            'url' => $storage->url($storedPath),
            'alt' => pathinfo((string) $image->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
    }
}
