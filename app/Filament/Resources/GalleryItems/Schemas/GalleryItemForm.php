<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;

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
                        TextInput::make('department')->default('general')->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
            ]);
    }
}
