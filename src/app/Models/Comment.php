<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'tip_id',
        'user_id',
        'body',
        'parent_id',
        'reply_to_id',
        'depth',
        'status',
    ];

    /**
     * 댓글이 속한 팁
     */
    public function tip(): BelongsTo
    {
        return $this->belongsTo(Tip::class);
    }

    /**
     * 댓글 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 부모 댓글
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 자식 댓글(대댓글)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * 실제 답글 대상 댓글
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'reply_to_id');
    }

    /**
     * 댓글 좋아요를 누른 사용자들
     */
    public function likedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'comment_likes',
            'comment_id',
            'user_id',
        );
    }
}
