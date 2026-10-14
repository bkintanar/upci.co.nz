<?php

namespace App\Filament\Resources\Events;

use BackedEnum;
use Filament\Panel;
use App\Models\Event;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Middleware\NationalOrRegionalOnly;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ViewEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Filament\Resources\Events\Schemas\EventInfolist;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
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
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'view' => ViewEvent::route('/{record}'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    /**
     * A regional user sees their own region's events and nothing else (owner,
     * 2026-10-15: "regional pastors should only see their data nothing
     * more"). National and department events, and other regions', are
     * national data. Board members who are also regional pastors hold
     * national access and keep the full calendar.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isNational()) {
            $query->where('region_id', $user->region_id ?? -1);
        }

        return $query;
    }

    public static function getRouteMiddleware(Panel $panel): string|array
    {
        return [NationalOrRegionalOnly::class];
    }
}
