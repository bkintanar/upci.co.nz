<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use App\Models\Region;
use App\Models\Department;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MorphToSelect;

class GalleryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gallery item')
                    ->schema([
                        TextInput::make('title')->maxLength(255),
                        Textarea::make('description')->rows(3),
                        FileUpload::make('image_path')
                            ->image()
                            // FILESYSTEM_DISK is `local`, and Filament v4 decides default
                            // visibility by literal disk-name match — without these the file
                            // lands in storage/app/private and is unservable.
                            ->disk('public')
                            ->visibility('public')
                            // ->image() alone accepts image/svg+xml, which can carry script
                            // and executes on our origin once the file is web-served.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->directory('gallery')
                            ->required(),
                        // Replaces a free-text `department` box that defaulted
                        // to the string "general". One gallery, three owners:
                        // leaving this blank IS the general gallery, which is
                        // why there is no "general" option to pick.
                        MorphToSelect::make('galleryable')
                            ->label('Belongs to')
                            ->types([
                                MorphToSelect\Type::make(Department::class)->titleAttribute('name'),
                                MorphToSelect\Type::make(Region::class)->titleAttribute('name'),
                            ])
                            ->nullable()
                            ->searchable()
                            ->preload(),

                        Toggle::make('is_published')
                            ->default(true)
                            ->helperText('Unpublished items stay out of every gallery.'),

                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
            ]);
    }
}
