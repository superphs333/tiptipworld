<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Tip extends Model
{
    /**
     * 대량 할당이 가능한 속성들
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];

    // Each tip belongs to one author.
    public function user()
    { 
        return $this->belongsTo(User::class);
    }
}
