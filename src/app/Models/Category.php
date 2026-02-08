<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tip;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];
    
    // tip과의 관계 : 1개의 팁은 1개의 카테고리에 속한다.
    public function tips()
    {
        return $this->hasMany(Tip::class);        
    }

    // 카테고리 목록 필터링용 스코프
    public function scopeFilter(Builder $query, ?string $isActive, ?string $name): Builder
    {
        return $query
            ->when($isActive !== null && $isActive !== '', function (Builder $query) use ($isActive) {
                $query->where('is_active', (int) $isActive);
            })
            ->when($name !== null && $name !== '', function (Builder $query) use ($name) {
                $query->where('name', 'like', "%{$name}%");
            });
    }

    // tip 작성 폼용 카테고리 목록
    public function scopeForTipForm(Builder $query) : Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }



}
