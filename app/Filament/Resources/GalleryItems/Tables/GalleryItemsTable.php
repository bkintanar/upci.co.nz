<?php

namespace App\Filament\Resources\GalleryItems\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class GalleryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('galleryable.name')
                    ->label('Belongs to')
                    ->placeholder('General gallery')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                ImageColumn::make('image_path')->disk('public')->label('Image'),
                TextColumn::make('title')->placeholder('—'),
                TextColumn::make('department')->badge(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
