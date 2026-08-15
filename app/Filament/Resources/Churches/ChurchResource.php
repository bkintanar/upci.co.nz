<?php

namespace App\Filament\Resources\Churches;

use App\Filament\Concerns\ScopesToAccessLevel;
use BackedEnum;
use App\Models\Church;
use Closure;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Churches\Pages\EditChurch;
use App\Filament\Resources\Churches\Pages\ViewChurch;
use App\Filament\Resources\Churches\Pages\CreateChurch;
use App\Filament\Resources\Churches\Pages\ListChurches;
use App\Filament\Resources\Churches\Schemas\ChurchForm;
use App\Filament\Resources\Churches\Tables\ChurchesTable;
use App\Filament\Resources\Churches\Schemas\ChurchInfolist;

class ChurchResource extends Resource
{
    use ScopesToAccessLevel;

    protected static ?string $model = Church::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 1;

    protected static function localScope(): Closure
    {
        return fn (Builder $q, int $churchId) => $q->where('id', $churchId);
    }

    protected static function regionalScope(): Closure
    {
        return fn (Builder $q, int $regionId) => $q->where('region_id', $regionId);
    }

    public static function form(Schema $schema): Schema
    {
        return ChurchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChurchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChurchesTable::configure($table);
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
            'index' => ListChurches::route('/'),
            'create' => CreateChurch::route('/create'),
            'view' => ViewChurch::route('/{record}'),
            'edit' => EditChurch::route('/{record}/edit'),
        ];
    }
}
