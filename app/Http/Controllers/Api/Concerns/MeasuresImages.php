<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Photo sizes for the API, so a page can lay out a photo by its shape (a tall
 * flyer beside its words, a wide photo across the line) before it loads, and
 * without the layout jumping.
 *
 * Moved from DepartmentController on 2026-10-17, to be shared with the
 * announcements block on CMS pages (PageController).
 */
trait MeasuresImages
{
    /**
     * Pixel size of an image on the public disk, read from the file's header.
     * Unknown (no photo, or a missing file) is null rather than a guess, and
     * the page then uses the layout that shows any photo whole.
     *
     * @return array{image_width: int|null, image_height: int|null}
     */
    protected function imageSize(?string $path): array
    {
        $size = null;

        if ($path && Storage::disk('public')->exists($path)) {
            $size = @getimagesize(Storage::disk('public')->path($path)) ?: null;
        }

        return [
            'image_width' => $size[0] ?? null,
            'image_height' => $size[1] ?? null,
        ];
    }
}
