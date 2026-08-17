<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventScope;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                        Textarea::make('description')->rows(4),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')->required(),
                            DatePicker::make('end_date'),
                        ]),
                        TextInput::make('location')->maxLength(255),
                        TextInput::make('url')->url()->maxLength(255),
                        Select::make('scope')
                            ->label('Calendar')
                            ->options(EventScope::options())
                            ->default(EventScope::NATIONAL->value)
                            ->required()
                            ->live()
                            // Switching away from regional must clear the region.
                            // A hidden field is not dehydrated, so without this the
                            // old region_id survives on a national event and the API
                            // then publishes a region for it.
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== EventScope::REGIONAL->value) {
                                    $set('region_id', null);
                                }
                            })
                            ->helperText('Which calendar this event belongs to. The public events page shows the national calendar; region pages show their own.'),

                        // Only meaningful for a regional event, and hidden
                        // otherwise so it cannot be set on a national one and
                        // then quietly ignored by Event::forRegion().
                        Select::make('region_id')
                            ->label('Region')
                            ->relationship('region', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn ($get) => $get('scope') === EventScope::REGIONAL->value)
                            ->visible(fn ($get) => $get('scope') === EventScope::REGIONAL->value)
                            ->dehydrated(),

                        Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Grid::make(2)->schema([
                            Toggle::make('is_published')->default(true),
                            TextInput::make('sort_order')->numeric()->default(0),
                        ]),
                    ]),
            ]);
    }
}
