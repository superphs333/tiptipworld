<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Tag extends Model
{

    protected $fillable = [
        'name',
        'is_blocked',
    ];

    protected $appends = [
        'created_date',
        'updated_date'
    ];


    /**
     * Tag - Tip (m:n)
     * pivot : tip_tag(tag_id, tip_id)
     */
    public function tips() : BelongsToMany{
        return $this->belongsToMany(Tip::class, 'tip_tag', 'tag_id', 'tip_id')->withTimestamps();
    }

    protected function createdDate() : Attribute
    {
        return Attribute::make(
            get : fn(mixed $value, array $attributes) => 
                \Illuminate\Support\Carbon::parse($attributes['created_at'])->format('Y-m-d H시i분s초')
        );
    }

    protected function updatedDate() : Attribute
    {
        return Attribute::make(
            get : fn(mixed $value, array $attributes) => 
                \Illuminate\Support\Carbon::parse($attributes['updated_at'])->format('Y-m-d H시i분s초')
        );
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
