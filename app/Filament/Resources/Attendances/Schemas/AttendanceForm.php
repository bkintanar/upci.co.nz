<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Event Block
                Section::make('Event Details')
                    ->description('Basic information about the event')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('event')
                                    ->label('Event Name')
                                    ->placeholder('e.g., Sunday Service, Prayer Meeting')
                                    ->maxLength(255),

                                DatePicker::make('date')
                                    ->label('Date')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->default(now()),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Attendance Block
                Section::make('Attendance Count')
                    ->description('Record attendance numbers')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('mens')
                                    ->label('Men')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('ladies')
                                    ->label('Ladies')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('youth')
                                    ->label('Youth')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('children')
                                    ->label('Children')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('visitors')
                                    ->label('Visitors')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Added by Block
                Section::make('Record Information')
                    ->description('Information about who is recording this data')
                    ->schema([
                        TextEntry::make('user_info')
                            ->state(fn () => Auth::user()->name.' ('.Auth::user()->email.')')
                            ->label('Added by')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(false)
                    ->columnSpanFull(),
            ]);
    }
}
