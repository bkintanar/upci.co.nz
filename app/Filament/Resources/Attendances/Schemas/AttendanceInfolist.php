<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Event Details Section
                Section::make('Event Details')
                    ->description('Basic information about the event')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('date')
                                    ->date(),
                                TextEntry::make('event')
                                    ->visible(fn ($record) => ! empty($record->event)),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Attendance Count Section
                Section::make(fn ($record) => 'Attendance Count: '.$record->total)
                    ->description('Breakdown of attendance numbers')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('mens')
                                    ->label('Men')
                                    ->numeric()
                                    ->visible(fn ($record) => $record->mens),
                                TextEntry::make('ladies')
                                    ->label('Ladies')
                                    ->numeric()
                                    ->visible(fn ($record) => $record->ladies),
                                TextEntry::make('youth')
                                    ->label('Youth')
                                    ->numeric()
                                    ->visible(fn ($record) => $record->youth),
                                TextEntry::make('children')
                                    ->label('Children')
                                    ->numeric()
                                    ->visible(fn ($record) => $record->children),
                                TextEntry::make('visitors')
                                    ->label('Visitors')
                                    ->numeric()
                                    ->visible(fn ($record) => $record->visitors),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Record Information Section
                Section::make('Record Information')
                    ->description('Information about who recorded this data')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Recorded by'),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created'),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Updated'),
                            ]),
                    ])
                    ->collapsible(false)
                    ->columnSpanFull(),
            ]);
    }
}
