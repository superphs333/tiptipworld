<?php

namespace App\Services\Media;

use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * 사용자 프로필 이미지 전용 서비스 클래스
 * : 프로필 이미지 저장, 교체, 외부 URL import, 삭제, URL 변환 
 */
class ProfileImageService
{
    public function __construct(
        private R2ImageStorageService $storage,
    ) {
    }

    /**
     * 사용자가 직접 업로드한 파일로 프로필 이미지 교체
     * 
     * [흐름]
     * 1. 기존 프로필 이미지 경로를 변수에 보관
     * 2. 새 이미지를 R2에 먼저 보관
     * 3. 새 이미지 경로를 User 모델의 profile_image_path에 저장
     * 4. DB 저장이 실패하면 방금 업르드한 새 이미지를 삭제 
     * 5. DB 저장이 성공하면 기존 이미지를 R2에서 삭제 
     * 6. 새 이미지 경로를 반환 
     * 
     * @param User $user : 프로필 이미지를 교체할 사용자 모델
     * @param UploadedFile $file : 사용자가 업로드한 이미지 파일
     * @param string|null $filename : 저장 파일명 일부에 사용할 이름 
     */
    public function replace(User $user, UploadedFile $file, ?string $filename = 'profile'): string
    {
    
        $oldPath = $user->profile_image_path; // 현재 사용자가 가지고 있는 기존 프로필 이미지 경로
        $newPath = $this->storage->store($file, $this->prefixFor($user), $filename);  // 새 프로필 이미지 

        try {
            $user->profile_image_path = $newPath;
            $user->save();
        } catch (\Throwable $e) {
            $this->storage->delete($newPath);

            throw $e;
        }

        // 성공 뒤, 기존 프로필 이미지를 r2에서 삭제
        $this->storage->delete($oldPath);

        // 새 이미지 경로 반환 
        return $newPath;
    }

    /**
     * 외부 이미지 url을 가져와서 사용자의 프로필 이미지로 저장
     * 
     * [흐름]
     * 1. 외부 URL 이미지를 R2에 저장
     * 2. 저장 실패 시 null반환
     * 3. 기존 프로필 이미지 경로를 보관
     * 4. User 모델 profile_image_path를 새 경로로 변경
     * 5. DB 저장 실패 시 새로 업로드한 이미지 삭제
     * 6. DB 저장 성공 시 기존 이미지를 삭제 
     * 7. 새 이미지 경로 반환 
     */
    public function importFromUrl(User $user, string $url, ?string $filename = 'profile'): ?string
    {
        $newPath = $this->storage->storeFromUrl($url, $this->prefixFor($user), $filename);

        if ($newPath === null) {
            return null;
        }

        $oldPath = $user->profile_image_path;

        try {
            $user->profile_image_path = $newPath;
            $user->save();
        } catch (\Throwable $e) {
            $this->storage->delete($newPath);

            throw $e;
        }

        if ($oldPath !== $newPath) {
            $this->storage->delete($oldPath);
        }

        return $newPath;
    }

    /**
     * 사용자의 프로필 이미지 제거
     * 
     * [흐름]
     * 1. 기존 프로필 이미지 경로를 보관
     * 2. User 모델의 profile_image_path을 null로 변경
     * 3. persist가 true이면 DB에 저장
     * 4. 기존 이미지를 R2에서 삭제 
     */
    public function remove(User $user, bool $persist = true): void
    {
        $oldPath = $user->profile_image_path;
        $user->profile_image_path = null;

        if ($persist) {
            $user->save();
        }

        $this->storage->delete($oldPath);
    }

    // 프로필 이미지 경로를 실제 화면에서 사용할 url로 변환 (프로필 이미지 없으면 기본 아바타 이미지 반환)
    public function url(?string $path): string
    {
        if (blank($path)) {
            return asset('images/avatar-default.svg');
        }

        return $this->storage->url($path);
    }

    private function prefixFor(User $user): string
    {
        return MediaPath::userProfile($user->id);
    }
}
