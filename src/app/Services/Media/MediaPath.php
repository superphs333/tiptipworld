<?php

namespace App\Services\Media;

use InvalidArgumentException;

/**
 * 미디어 파일 저장 경로를 생성하는 전용 클래스 
 * : 어떤 종류의 이미지를 R2의 어느 폴더에 저장할 것인지를 일관된 규칙으로 만들어줌
 */
final class MediaPath
{
    // 모든 미디어 파일 경로의 최상위 루트 폴더명 
    private const ROOT = 'media';

    // 사용자 프로필 : users/{userId}/profile
    public static function userProfile(int|string $userId): string
    {
        return self::build('users', (string) $userId, 'profile');
    }

    // 게시글 본문 에디터 저장 경로 : media/posts/30/editor
    public static function postEditor(int|string $postId): string
    {
        return self::build('posts', (string) $postId, 'editor');
    }

    // 아직 저장되지 않은 임시 게시글의 에디터 이미지 저장 경로
    // - 개별 draft 경로 : media/posts/drafts/{userId}/{draftKey}/editor
    public static function draftPostEditor(int|string $userId, string $draftKey): string
    {
        return self::build('posts', 'drafts', (string) $userId, $draftKey, 'editor');
    }

    // 게시글 썸네일 이미지 저장 경로 : media/posts/30/thumbnails
    public static function postThumbnails(int|string $postId): string
    {
        return self::build('posts', (string) $postId, 'thumbnails');
    }

    /**
     * 여러 개의 경로 조각을 하나의 미디어 저장 경로로 조립 
     * 
     * [처리흐름]
     * 1. 각 segment의 앞뒤 슬래시 제거
     * 2. 빈 segemnt가 있는지 검증
     * 3. 맨 앞에 ROOT인 media 추가
     * 4. 슬래시(/)로 연결해서 최종 경로 반환 
     */
    private static function build(string ...$segments): string
    {
        // 전달받은 모든 segemnt를 순회하면서 리
        $normalizedSegments = array_map(
            static function (string $segment): string {
                $segment = trim($segment, '/'); // 앞 뒤 슬래시 제거

                if ($segment === '') { // 빈 문자열 여부 검사 
                    throw new InvalidArgumentException('미디어 경로 segment는 비어 있을 수 없습니다.');
                }

                return $segment; // 정리된 segment 반환 
            },
            $segments,
        );

        // 정리된 sement 배열 맨 앞에 ROOT 값을 추가 
        array_unshift($normalizedSegments, self::ROOT);

        // 모든 segment를 슬래시로 연결해 최종 경로 만들기
        return implode('/', $normalizedSegments);
    }
}
