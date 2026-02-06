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
}
