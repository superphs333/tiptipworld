<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Tag;


class Tip extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'thumbnail',
        'content',
        'excerpt',
        'status',
        'visibility',
        'published_at',
        'tags_count',
        'view_count',
        'like_count',
    ];

    /**
     * Tip - Category (N:1)
     * tips.category_id -> caregories.id
     */
    public function category(){
        return $this->belongsTo(Category::class);
    }

    /**
     * Tip - Tag (M:N)
     * pivot : tip_tag (tip_id, tag_id)
     */
    public function tags() : BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tip_tag','tip_id', 'tag_id')->withTimestamps();
    }
}
