<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tip;
use Illuminate\Foundation\Http\FormRequest;

class StoreTipRequest extends FormRequest
{
    // Only logged-in users with create permission can post tips.
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tip::class) ?? false;
    }

    // Basic validation rules for new tips.
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
}
