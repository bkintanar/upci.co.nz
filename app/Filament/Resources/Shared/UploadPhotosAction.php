<?php

namespace App\Filament\Resources\Shared;

use Closure;
use App\Models\GalleryItem;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * "Upload photos": many photos at once, each becoming its own gallery item.
 *
 * The create form takes one photo, so loading an event's 20 photos meant 20
 * round-trips through it. Items are appended after the gallery's current
 * last one, in file-name order, and can each be edited afterwards.
 *
 * Used in two places, which is why it is its own class:
 * - GalleryItemsRelationManager, on a department or region, where the owner
 *   is the record being edited;
 * - the Gallery list (ListGalleryItems), for the general gallery, whose
 *   items have no owner (owner request, 2026-10-17).
 *
 * It does not authorise itself. Each caller adds ->authorize(), because who
 * may upload differs between the two.
 */
final class UploadPhotosAction
{
    /**
     * @param  Closure(): ?Model  $owner  the Department or Region whose gallery
     *                                    receives the photos, or null for the
     *                                    general gallery
     */
    public static function make(Closure $owner): Action
    {
        return Action::make('uploadPhotos')
            ->label('Upload photos')
            ->icon(Heroicon::OutlinedPhoto)
            ->modalHeading('Upload photos')
            ->modalDescription('Add up to 30 photos at once. Each becomes its own gallery item, in file-name order (for phone and camera photos, the order they were taken). You can give them a shared caption and edit any of them afterwards.')
            ->modalSubmitActionLabel('Upload')
            ->schema([
                PhotoUpload::make('photos', 'gallery')
                    ->label('Photos')
                    ->multiple()
                    ->minFiles(1)
                    // Livewire throttles uploads at 60 a minute.
                    ->maxFiles(30)
                    ->appendFiles()
                    // The original names, kept beside the stored (random,
                    // safe) file names, set the order below. The order shown
                    // in the panel cannot: Filament stores uploads in the
                    // order they finish, and each photo is resized in the
                    // browser first, so a later photo can finish first (seen
                    // in the 2026-10-15 browser check). No drag-to-reorder,
                    // because the sort would undo it.
                    ->storeFileNamesIn('photo_names')
                    ->panelLayout('grid')
                    ->required(),
                TextInput::make('caption')
                    ->label('Caption for these photos (optional)')
                    ->maxLength(255)
                    ->helperText('Used as each photo\'s title, e.g. "Men\'s Rally, Queenstown 2026". Screen readers read it, so say what the photos show.'),
                Toggle::make('is_published')
                    ->label('Published')
                    ->default(true),
            ])
            ->action(function (array $data) use ($owner): void {
                $owner = $owner();

                // The owner's own items, or the general gallery's: the items
                // that nobody owns.
                $gallery = fn () => $owner ? $owner->galleryItems() : GalleryItem::query()->general();

                // By now Filament has stored the files and $data['photos'] holds
                // their paths. Validation (count, type, size) ran before any
                // file was stored, so a refused batch leaves nothing behind.
                $count = DB::transaction(function () use ($owner, $gallery, $data): int {
                    $next = (int) $gallery()->max('sort_order');

                    // File-name order, by number (IMG_99 before IMG_0101): for
                    // phone and camera photos, the order they were taken.
                    $names = $data['photo_names'] ?? [];
                    $paths = array_values($data['photos']);
                    usort($paths, fn (string $a, string $b): int => strcmp(
                        self::fileNameSortKey($names[$a] ?? $a),
                        self::fileNameSortKey($names[$b] ?? $b),
                    ));

                    foreach ($paths as $i => $path) {
                        $attributes = [
                            'title' => filled($data['caption'] ?? null) ? $data['caption'] : null,
                            'image_path' => $path,
                            'is_published' => (bool) ($data['is_published'] ?? true),
                            'sort_order' => $next + $i + 1,
                        ];

                        $owner ? $owner->galleryItems()->create($attributes) : GalleryItem::create($attributes);
                    }

                    return count($data['photos']);
                });

                Notification::make()->success()
                    ->title($count === 1 ? '1 photo added' : "{$count} photos added")
                    ->send();
            });
    }

    /**
     * A file name with every number padded to one width, so plain string
     * order is numeric order. PHP's natural sort treats a leading zero as a
     * decimal fraction and would put IMG_0101 before IMG_99.
     */
    private static function fileNameSortKey(string $name): string
    {
        return mb_strtolower(preg_replace_callback('/\d+/', fn (array $m): string => str_pad(ltrim($m[0], '0'), 20, '0', STR_PAD_LEFT), $name));
    }
}
