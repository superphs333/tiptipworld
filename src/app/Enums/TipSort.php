<?php

namespace App\Enums;

enum TipSort: string
{
    case Latest = 'latest';
    case Popular = 'popular';
    case Likes = 'likes';
    case Bookmarks = 'bookmarks';

    public function label(): string
    {
        return match ($this) {
            self::Latest => '최신순',
            self::Popular => '조회순',
            self::Likes => '좋아요순',
            self::Bookmarks => '북마크순',
        };
    }

    public static function fromNullable(?string $value): self
    {
        return self::tryFrom(trim((string) $value)) ?? self::Latest;
    }
}
