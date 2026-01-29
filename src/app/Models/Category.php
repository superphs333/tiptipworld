<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Tip;
use Cocur\Slugify\Slugify;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    
    // tip과의 관계 : 1개의 팁은 1개의 카테고리에 속한다.
    public function tips()
    {
        return $this->hasMany(Tip::class);        
    }

    // 카테고리 목록 가져오기 
    public function getCategories($is_active=null, $name=null)
    {
       $items = $this->query()
       ->when($is_active !== null && $is_active !== '', function($query) use ($is_active){
            $query->where('is_active', (int) $is_active);
       })
       ->when($name, function($query, $name){
            $query->where('name','like', "%{$name}%");
       });
       return $items->get();
    }

}
