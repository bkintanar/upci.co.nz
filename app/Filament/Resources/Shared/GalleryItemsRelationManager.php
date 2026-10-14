<?php

namespace App\Filament\Resources\Shared;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * One relation manager shared by Department and Region, mounted on both.
 *
 * Requirement 2 explicitly asks not to build the gallery three times, and that
 * applies to the admin as much as the data model — two near-identical relation
 * managers would drift the moment either is edited. The owner is set implicitly
 * by whichever record you are editing, so there is no owner picker here.
 */
class GalleryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'galleryItems';

    protected static ?string $title = 'Gallery';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gallery item')
                    ->schema([
                        TextInput::make('title')->maxLength(255),
                        Textarea::make('description')->rows(3),
                        // Public disk, jpeg/png/webp only, resized in the
                        // browser: see PhotoUpload for why each rule exists.
                        PhotoUpload::make('image_path', 'gallery')->required(),
                        Grid::make(2)->schema([
                            Toggle::make('is_published')->default(true),
                            TextInput::make('sort_order')->numeric()->default(0),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->height(48),
                TextColumn::make('title')->searchable()->sortable()->placeholder('Untitled'),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                $this->uploadPhotosAction(),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Many photos at once, each becoming its own gallery item.
     *
     * The create form takes one photo, so loading an event's 20 photos meant 20
     * round-trips through it. Items are appended after the current last one,
     * in the order shown in the upload panel, and can each be edited
     * afterwards. Shared by Department and Region like the rest of this
     * manager, so the owner is always the record being edited.
     */
    protected function uploadPhotosAction(): Action
    {
        return Action::make('uploadPhotos')
            ->label('Upload photos')
            ->icon(Heroicon::OutlinedPhoto)
            ->modalHeading('Upload photos')
            ->modalDescription('Add up to 30 photos at once. Each becomes its own gallery item, in file-name order (for phone and camera photos, the order they were taken). You can give them a shared caption and edit any of them afterwards.')
            ->modalSubmitActionLabel('Upload')
            // Custom actions are not authorised automatically in a relation
            // manager; only Create, Edit, Delete and the like are
            // (RelationManager::getDefaultActionAuthorizationResponse). Mirror
            // CreateAction: never on a read-only (View) page, and only when the
            // gallery policy allows create.
            ->authorize(fn (): bool => ! $this->isReadOnly() && $this->canCreate())
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
            ->action(function (array $data): void {
                $owner = $this->getOwnerRecord();

                // By now Filament has stored the files and $data['photos'] holds
                // their paths. Validation (count, type, size) ran before any
                // file was stored, so a refused batch leaves nothing behind.
                $count = DB::transaction(function () use ($owner, $data): int {
                    $next = (int) $owner->galleryItems()->max('sort_order');

                    // File-name order, by number (IMG_99 before IMG_0101): for
                    // phone and camera photos, the order they were taken.
                    $names = $data['photo_names'] ?? [];
                    $paths = array_values($data['photos']);
                    usort($paths, fn (string $a, string $b): int => strcmp(
                        self::fileNameSortKey($names[$a] ?? $a),
                        self::fileNameSortKey($names[$b] ?? $b),
                    ));

                    foreach ($paths as $i => $path) {
                        $owner->galleryItems()->create([
                            'title' => filled($data['caption'] ?? null) ? $data['caption'] : null,
                            'image_path' => $path,
                            'is_published' => (bool) ($data['is_published'] ?? true),
                            'sort_order' => $next + $i + 1,
                        ]);
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
