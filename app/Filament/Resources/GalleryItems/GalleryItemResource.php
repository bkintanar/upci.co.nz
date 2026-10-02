<?php

namespace App\Filament\Resources\GalleryItems;

use BackedEnum;
use Filament\Tables\Table;
use App\Models\GalleryItem;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\GalleryItems\Pages\EditGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\ViewGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Filament\Resources\GalleryItems\Pages\CreateGalleryItem;
use App\Filament\Resources\GalleryItems\Schemas\GalleryItemForm;
use App\Filament\Resources\GalleryItems\Tables\GalleryItemsTable;
use App\Filament\Resources\GalleryItems\Schemas\GalleryItemInfolist;

class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Gallery';

    /**
     * The standalone resource is national-only for create. GalleryItemPolicy
     * still allows regional create, because inside RegionResource's relation
     * manager the owner is fixed to their own region. Here the owner comes
     * from the form, which a regional user cannot set.
     */
    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->isNational() && parent::canCreate();
    }

    public static function form(Schema $schema): Schema
    {
        return GalleryItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GalleryItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GalleryItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryItems::route('/'),
            'create' => CreateGalleryItem::route('/create'),
            'view' => ViewGalleryItem::route('/{record}'),
            'edit' => EditGalleryItem::route('/{record}/edit'),
        ];
    }
}
