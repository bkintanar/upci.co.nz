<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Shared\UploadPhotosAction;
use App\Filament\Resources\GalleryItems\GalleryItemResource;

class ListGalleryItems extends ListRecords
{
    protected static string $resource = GalleryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The general gallery's bulk upload (owner request, 2026-10-17).
            // A department's or region's photos are uploaded from that
            // department's or region's own page, so here the owner is
            // always nobody. National only, like creating an item here.
            UploadPhotosAction::make(fn () => null)
                ->modalHeading('Upload photos to the general gallery')
                ->authorize(fn (): bool => GalleryItemResource::canCreate()),
            CreateAction::make(),
        ];
    }
}
