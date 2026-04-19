<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                            ->directory('gallery')
                            ->required(),
                        TextInput::make('department')->default('general')->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
            ]);
    }
}
