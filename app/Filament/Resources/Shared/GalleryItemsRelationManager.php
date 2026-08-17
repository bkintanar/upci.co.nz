<?php

namespace App\Filament\Resources\Shared;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
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
                        FileUpload::make('image_path')
                            ->image()
                            // See GalleryItemForm — FILESYSTEM_DISK is `local`,
                            // and ->image() alone accepts SVG, which can carry
                            // script once the file is web-served.
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->directory('gallery')
                            ->required(),
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
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
