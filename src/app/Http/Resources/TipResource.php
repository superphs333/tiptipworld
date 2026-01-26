<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TipResource extends JsonResource
{
    // Shape the data for both list and detail views.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => Str::limit((string) $this->content, 180),
            'content' => $this->when(
                $request->routeIs('tips.show', 'tips.edit'),
                $this->content
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'user' => [
                'name' => $this->user?->name ?? '알 수 없음',
            ],
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
