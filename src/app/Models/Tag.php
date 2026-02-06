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

    public static function getTags(array $filters = [], int $perPage = 20){
        $q = Tag::query()->with('tips');

        /**
         * 필터
         */
        $isBlocked = $filters['is_blocked'] ?? null;
        if ($isBlocked !== null && $isBlocked !== '') {
            $q->where('is_blocked', (int) $isBlocked);
        }

        if (isset($filters['query'])) {
            $keyword = trim((string) $filters['query']);
            if ($keyword !== '') {
                $q->where('name', 'like', '%' . $keyword . '%');
            }
        }

        return $q->orderBy('id')->paginate($perPage)->withQueryString();
    }
}
