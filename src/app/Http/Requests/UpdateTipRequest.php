<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tip;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTipRequest extends FormRequest
{
    // Only the owner can edit a tip.
    public function authorize(): bool
    {
        $tip = $this->route('tip');

        return $tip instanceof Tip
            ? $this->user()?->can('update', $tip) ?? false
            : false;
    }

    // Same rules as create.
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
}
