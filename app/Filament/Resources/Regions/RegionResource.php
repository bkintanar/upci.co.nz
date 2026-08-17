<?php

namespace App\Filament\Resources\Regions;

use BackedEnum;
use App\Models\Region;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Filament\Resources\Regions\Schemas\RegionForm;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Filament\Resources\Shared\GalleryItemsRelationManager;

/**
 * Regions are a fixed taxonomy (Northern, Central, Southern), so there is no
 * create page and no delete action — RegionPolicy denies both to everyone but
 * national, and the pages simply are not registered. A regional presbyter
 * lands here to edit their own region's content and sees nothing else.
 */
class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Regions';

    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    /**
     * Scope, not just policy. The policy stops a regional user OPENING another
     * region; this stops the list page from naming one at all. Without it the
     * table would happily render all three rows and only fail on click.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isNational()) {
            $query->whereKey($user->region_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            GalleryItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }
}
