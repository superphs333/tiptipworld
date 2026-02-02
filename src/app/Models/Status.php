<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    public static function getStatuses(){
        return Status::query()->pluck('name')->all();
    }
}
