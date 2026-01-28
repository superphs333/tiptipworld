<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tip;

class Category extends Model
{
    // tip과의 관계 : 1개의 팁은 1개의 카테고리에 속한다.
    public function tips()
    {
        return $this->hasMany(Tip::class);        
    }

    // 카테고리 목록 가져오기 
    public function getCategories($is_active=null, $name=null)
    {
       $items = $this->query()
       ->when($is_active, function($query, $is_active){
            $query->where('is_active', $is_active);
       })
       ->when($name, function($query, $name){
            $query->where('name','like', "%{$name}%");
       });
       return $items->get();
    }
}
