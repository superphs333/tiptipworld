<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * R2 이미지 저장 전용 서비스 클래스
 * : Cloudeflare R2같은 S3 호환 스토리지에 이미지를 저장, 삭제, URL 변환
 * 
 * [주요책임]
 * - 사용자가 업로드한 이미지 파일 저장
 * - 외부 URL 이미지 다운로드 후 저장
 * - 저장된 이미지 삭제
 * - 저장된 이미지 경로를 실제 접근 가능한 URL 로 변환
 */
class R2ImageStorageService
{
    // 이미지 저장할 Laravel Filesystem disk 
    private const DISK = 'r2';

    //  허용할 이미지 MIME 타입과 실제 저장 확장자 
    private const IMAGE_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    /**
     * 사용자가 업로드한 이미지 파일을 R2에 저장
     * 
     * [흐름]
     * 1. 저장 폴더 prefix 정리
     * 2. 업로드 파일의 MIME타입 확인
     * 3. MIME 타입에 맞는 확장자 결정
     * 4. UUID 기반 저장 경로 생성
     * 5. 파일을 stream으로 열어서 R2에 업로드
     * 6. 업로드 성공 시 저장 경로 반환 
     * 
     * @param UploadeFile $file : 라라벨이 요청에서 받은 업로드 파일 객체 
     * @return string : R2에 저장된 상대 경로 (ex.profiles/550e8400-e29b-41d4-a716-446655440000.jpg)
     * 
     * @throws InvalidArgumentException : prefix가 비었거나, 지원하지 않은 이미지 MIME 타입인 경우
     * @throws RutimeException : 파일을 읽을 수 없거나 R2 업로드에 실패한 경우 
     */
    public function store(UploadedFile $file, string $prefix, ?string $filename = null): string
    {
        $prefix = $this->normalizePrefix($prefix);

        
        $mimeType = Str::lower($file->getMimeType() ?? ''); // 업로드된 파일의 MIME 타입 가져오기 
        $extension = $this->extensionFromMime($mimeType); // MIME타입에 대응되는 확장자 가져오기 (허용하지 않은 타입이면 NULL)

        if ($extension === null) {
            throw new InvalidArgumentException('지원하지 않는 이미지 형식입니다.');
        }

        // 실제 R2에 저장될 경로 만들기 
        $path = $this->buildPath($prefix, $extension, $filename);

        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('업로드 파일을 읽을 수 없습니다.');
        }

        try {
            $stored = Storage::disk(self::DISK)->put($path, $stream, [
                'visibility' => 'public',
                'ContentType' => $mimeType,
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw new RuntimeException('R2 이미지 업로드에 실패했습니다.');
        }

        return $path;
    }

    public function storeFromUrl(string $url, string $prefix, ?string $filename = null): ?string
    {
        $prefix = $this->normalizePrefix($prefix);

        if (blank($url)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->accept('image/*')
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $mimeType = Str::before(
            Str::lower($response->header('Content-Type') ?? ''),
            ';',
        );

        $extension = $this->extensionFromMime($mimeType);

        if ($extension === null) {
            return null;
        }

        $path = $this->buildPath($prefix, $extension, $filename);

        $stored = Storage::disk(self::DISK)->put($path, $response->body(), [
            'visibility' => 'public',
            'ContentType' => $mimeType,
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        return $stored ? $path : null;
    }

    // 주어진 경로의 파일이 R2에 실제로 존재하는지 확인
    public function exists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        return Storage::disk(self::DISK)->fileExists(ltrim($path, '/'));
    }

    // R2 내부에서 파일을 다른 경로로 이동 
    public function move(string $sourcePath, string $destinationPath): void
    {
        // 원본/대상 경로를 정리 : 앞뒤 공백 제거, 맨 앞 / 제거, 비어 있으면 예외 발생 
        $sourcePath = $this->normalizeStoredPath($sourcePath, '원본 이미지 경로');
        $destinationPath = $this->normalizeStoredPath($destinationPath, '대상 이미지 경로');

        // 원본과 대상이 같으면 이는 원하는 상태이므로 아무 것도 하지 않음 
        if ($sourcePath === $destinationPath) {
            return;
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->fileExists($sourcePath)) {
            throw new RuntimeException('이동할 R2 이미지가 존재하지 않습니다.');
        }

        if (! $disk->move($sourcePath, $destinationPath)) {
            throw new RuntimeException('R2 이미지 이동에 실패했습니다.');
        }

        // move()가 성공해도 실제 상태를 한 번 더 검증
            // 원본이 아직 남아 있거나, 대상 파일이 안 생겼으면 -> 실제 이동이 정상 완료되지 않은 것으로 판단 
        if ($disk->fileExists($sourcePath) || ! $disk->fileExists($destinationPath)) {
            throw new RuntimeException('R2 이미지가 실제로 이동되지 않았습니다.');
        }
    }

    // 특정 prefix(폴더 경로) 아래의 모든 파일 목록 조회 
    public function files(string $prefix): array
    {
        // prefix 앞뒤 / 제거, 빈 값이면 예외
        $prefix = $this->normalizePrefix($prefix);

        return array_map(
            static fn (string $path): string => ltrim($path, '/'),
            Storage::disk(self::DISK)->allFiles($prefix),
        );
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = ltrim($path, '/');
        $disk = Storage::disk(self::DISK);

        if (! $disk->fileExists($path)) {
            return;
        }

        if (! $disk->delete($path)) {
            throw new RuntimeException('R2 이미지 삭제에 실패했습니다.');
        }

        if ($disk->fileExists($path)) {
            throw new RuntimeException('R2 이미지가 실제로 삭제되지 않았습니다.');
        }
    }

    public function url(string $path): string
    {
        if (blank($path)) {
            throw new InvalidArgumentException('이미지 경로가 비어 있습니다.');
        }

        return Storage::disk(self::DISK)->url(ltrim($path, '/'));
    }

    /**
     * R2에 저장할 최종 파일 경로 생성
     * 
     * [저장 경로 구조]
     * 1. filename이 없는 경우 : {prefix}/{uuid}.{extension} ex) profiles/550e8400-e29b-41d4-a716-446655440000.jpg
     * 2. filename이 있는 경우 : {prefix}/{정리된파일명}-{uuid}.{extension} ex) profiles/profile-image-550e8400-e29b-41d4-a716-446655440000.jpg
     * 
     * @param string $prefix : 저장 폴더 경로
     * @param string $extension : MIME 타입에서 결정된 확장자
     * @param string|null $filname : 원본 파일명 또는 사용자가 지정한 파일명
     * 
     * @return string : 최종 저장 경로
     */
    private function buildPath(string $prefix, string $extension, ?string $filename = null): string
    {
        $uuid = Str::uuid()->toString();
        $name = filled($filename)
            ? sprintf('%s-%s', $this->sanitizeFilename($filename), $uuid)
            : $uuid;

        return sprintf('%s/%s.%s', $prefix, $name, $extension);
    }

    // prefix 앞뒤에 슬래시를 제거하고, 빈 값인지 검증
    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, '/');

        if ($prefix === '') {
            throw new InvalidArgumentException('저장 prefix는 비어 있을 수 없습니다.');
        }

        return $prefix;
    }

    private function normalizeStoredPath(string $path, string $fieldName): string
    {
        $path = ltrim(trim($path), '/');

        if ($path === '') {
            throw new InvalidArgumentException($fieldName . '가 비어 있습니다.');
        }

        return $path;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = pathinfo($filename, PATHINFO_FILENAME);

        $filename = Str::of($filename)
            ->trim()
            ->lower()
            ->slug('-')
            ->value();

        if ($filename === '') {
            throw new InvalidArgumentException('파일명은 비어 있을 수 없습니다.');
        }

        return $filename;
    }

    private function extensionFromMime(?string $mimeType): ?string
    {
        return self::IMAGE_EXTENSIONS[$mimeType] ?? null;
    }
}
