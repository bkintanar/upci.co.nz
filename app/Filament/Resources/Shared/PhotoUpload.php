<?php

namespace App\Filament\Resources\Shared;

use Filament\Forms\Components\FileUpload;

/**
 * One definition of a photo upload, used by announcements and galleries.
 *
 * The same field was copy-pasted four times with sizes of 4096, 5120 and 5120,
 * so one phone photo could upload in one place and be refused in another.
 *
 * - Public disk with public visibility. FILESYSTEM_DISK is `local`, and a
 *   FileUpload that does not name its disk writes into private storage, where
 *   the image 404s on the public site. Filament v4 also picks the default
 *   visibility by a literal match on the disk name, so it is set explicitly.
 * - jpeg/png/webp only. ->image() alone accepts image/svg+xml, which can carry
 *   script and executes on our origin once the file is web-served.
 * - 10 MB and an in-browser resize to at most 2000px. Phone photos are 3-12 MB;
 *   FilePond checks the size of the original, then shrinks it before upload, so
 *   each request stays under Livewire's 12 MB temporary limit and staging's
 *   25 MB body limit, and pages stay light without a server-side thumbnail
 *   step. 'contain' never crops (a flyer keeps its edges) and nothing small is
 *   upscaled. EXIF orientation is applied by default.
 */
class PhotoUpload
{
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_KB = 10240;

    public const MAX_EDGE = '2000';

    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->visibility('public')
            ->acceptedFileTypes(self::TYPES)
            ->maxSize(self::MAX_KB)
            ->directory($directory)
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth(self::MAX_EDGE)
            ->automaticallyResizeImagesToHeight(self::MAX_EDGE)
            ->automaticallyUpscaleImagesWhenResizing(false);
    }
}
