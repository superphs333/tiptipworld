<?php

namespace App\Services\Media;

use App\Models\Tip;
use Illuminate\Http\UploadedFile;

class TipThumbnailService
{
    public function __construct(
        private R2ImageStorageService $storage,
    ) {
    }

    public function store(Tip $tip, UploadedFile $file, ?string $filename = 'cover'): string
    {
        return $this->storage->store($file, $this->prefixFor($tip), $filename);
    }

    public function remove(Tip $tip, bool $persist = true): void
    {
        $oldPath = $tip->thumbnail;
        $tip->thumbnail = null;

        if ($persist) {
            $tip->save();
        }

        $this->storage->delete($oldPath);
    }

    public function deletePath(?string $path): void
    {
        $this->storage->delete($path);
    }

    public function url(?string $path): string
    {
        if (blank($path)) {
            return asset('images/no-thumbnail.png');
        }

        return $this->storage->url($path);
    }

    private function prefixFor(Tip $tip): string
    {
        return MediaPath::postThumbnails($tip->id);
    }
}
