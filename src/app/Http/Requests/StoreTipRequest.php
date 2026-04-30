<?php

namespace App\Http\Requests;

use App\Models\Tip;

class StoreTipRequest extends TipRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tip::class) ?? false;
    }
}
