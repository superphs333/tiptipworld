<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileStorageService
{

    // 현재 사용하는 디스크 
    private string $disk = 'r2';

    /**
     * 업로드된 파일을 컨텍스트에 맞는 경로로 저장
     * ex) profile, post, cover 등
     */
    public function storeUploaded(UploadedFile $file, string $context): string
    {

        // 컨텍스트별 설정(경로, 제한, 확장자) 조회
        $config = $this->configFor($context);

        // 업로드 파일 크기/확장자 검증
        $this->validateUploadedFile($file, $config);

        // 파일 확장자를 가져와 고유한 파일명 생성
        $extension = (string) $file->extension();
        $filename = Str::uuid()->toString() . '.' . $extension;

        // 최종 경로 생성 : prefix + filename 
        $path = $config['prefix'] . '/' . $filename;

        // 실제 파일 내용을 읽어서 지정 디스크에 저장
        $stored  = Storage::disk($this->disk)->put(
            $path,
            file_get_contents($file->getRealPath()),
            $config['visibility']
        );

        // 저장 실패 시 예외
        if (! $stored) {
            throw new RuntimeException('File storage failed');  
        }

        // 저장된 경로 반환
        return $path;
    }

    /**
     * Backward-compatible typo alias.
     * @deprecated Use storeUploaded() instead.
     */
    public function sotreUploaded(UploadedFile $file, string $context): string
    {
        return $this->storeUploaded($file, $context);
    }

    /**
     * 원격/외부 이미지 내용 저장
     * ex) OAuth 프로필 이미지 다운로드 후 저장
     */
    public function storeRemote(string $content, string $context, string $extension): ?string
    {
        $config  = $this->configFor($context);

        // 원격 이미지 확장자 허용 여부 확인
        if (! $this->isAllowedExtension($extension, $config['extensions'])) {
            return null;
        }

        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = $config['prefix'] . '/' . $filename;

        $stored = Storage::disk($this->disk)->put(
            $path,
            $content,
            $config['visibility']
        );

        return $stored ? $path : null;

    }

    /**
     *  삭제
     * - 경로가 있을 때만 안전하게 삭제
     */
    public function deleteIfExists(?string $path): void
    {
        if (! $path) {
            return;
        }
        Storage::disk($this->disk)->delete($path);
    }

    /**
     * 파일 URL 생성 (없으면 fallback URL 반환)
     */
    public function url(?string $path, string $fallbackUrl = ''): string
    {
        if (! $path) {
            return $fallbackUrl;        
        }
        return Storage::disk($this->disk)->url($path);
    }


    /**
     * 컨텍스트별 설정 정의 (확장 시 이 부분 수정)
     */
    private function configFor(string $context): array
    {
        return match ($context) {
            // 프로필 이미지
            'profile' => [
                'prefix' => 'profile-images',
                'max_kb' => 2048,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'visibility' => 'public',
            ],
            'profile_kakao' => [
                'prefix' => 'profile-images/kakao',
                'max_kb' => 2048,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
                'visibility' => 'public',
            ],
            'profile_google' => [
                'prefix' => 'profile-images/google',
                'max_kb' => 2048,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
                'visibility' => 'public',
            ],
            // 게시물 이미지
            'tip-post' => [
                'prefix' => 'tip-post-images',
                'max_kb' => 10240,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'visibility' => 'public',
            ],
            // 커버 이미지
            'tip-cover' => [
                'prefix' => 'tip-cover-images',
                'max_kb' => 5120,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'visibility' => 'public',
            ],
            // 미지정 컨텍스트는 예외 처리
            default => throw new RuntimeException("Unknown context: {$context}"),
        };
    }

    /**
     * 업로드 파일 검증 : 크기 및 확장자
     */
    private function validateUploadedFile(UploadedFile $file, array $config): void
    { 
        $sizeKb = (int) ceil($file->getSize() / 1024);

        // 최대 크기 제한
        if ($sizeKb > $config['max_kb']) {
            throw new RuntimeException('File size exceed limit');
        }

        // 확장자 제한
        $ext = strtolower((string) $file->extension());
        if ($ext === '' || ! $this->isAllowedExtension($ext, $config['extensions'])) {
            throw new RuntimeException('File extension not allowed');            
        }

    }

    /**
     * 확장자 허용 여부 검사 
     */
    private function isAllowedExtension(string $ext, array $allowed): bool
    {
        return in_array(strtolower($ext), $allowed, true);
    }
    
}
