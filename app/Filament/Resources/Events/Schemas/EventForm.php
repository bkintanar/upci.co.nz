<?php

namespace App\Filament\Resources\Events\Schemas;

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
