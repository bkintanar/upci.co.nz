<?php

namespace App\Filament\Resources\AGSUpdates;

use App\Filament\Resources\AGSUpdates\Pages\CreateAGSUpdate;
use App\Filament\Resources\AGSUpdates\Pages\EditAGSUpdate;
use App\Filament\Resources\AGSUpdates\Pages\ListAGSUpdates;
use App\Filament\Resources\AGSUpdates\Pages\ViewAGSUpdate;
use App\Filament\Resources\AGSUpdates\Schemas\AGSUpdateForm;
use App\Filament\Resources\AGSUpdates\Schemas\AGSUpdateInfolist;
use App\Filament\Resources\AGSUpdates\Tables\AGSUpdatesTable;
use App\Models\AGSUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AGSUpdateResource extends Resource
{
    protected static ?string $model = AGSUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'AGS Updates';

    public static function form(Schema $schema): Schema
    {
        return AGSUpdateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AGSUpdateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AGSUpdatesTable::configure($table);
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
            'index' => ListAGSUpdates::route('/'),
            'create' => CreateAGSUpdate::route('/create'),
            'view' => ViewAGSUpdate::route('/{record}'),
            'edit' => EditAGSUpdate::route('/{record}/edit'),
        ];
    }
}
