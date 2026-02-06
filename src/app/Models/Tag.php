<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{

    protected $fillable = [
        'name',
        'is_blocked',
    ];


    /**
     * Tag - Tip (m:n)
     * pivot : tip_tag(tag_id, tip_id)
     */
    public function tips() : BelongsToMany{
        return $this->belongsToMany(Tip::class, 'tip_tag', 'tag_id', 'tip_id')->withTimestamps();
    }

    public static function getTags(array $fillters = [], int $perPage = 20){
        $q = Tag::query()->with('tips');

        /**
         * 필터 
         */
        if(!empty($fillters['is_blocked'])){
            $q->where('is_blocked', $fillters['is_blocked']);
        }
        if(!empty($fillters['query'])){
            $keyword = trim($fillters['query']);
            $q->where('name', 'like', '%'.$keyword.'%');
        }

        return $q->orderBy('id')->paginate($perPage)->withQueryString();
    }
}
