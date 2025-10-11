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
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('church.name')
                                    ->label('Church')
                                    ->placeholder('No church assigned')
                                    ->visible(fn ($record) => $record->church_id),

                                TextEntry::make('date')
                                    ->date(),

                                TextEntry::make('event')
                                    ->placeholder('No event specified')
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

                                TextEntry::make('user.role')
                                    ->label('Role')
                                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'Member')
                                    ->badge()
                                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),

                                TextEntry::make('user.church.name')
                                    ->label('User\'s Church')
                                    ->placeholder('No church assigned')
                                    ->visible(fn ($record) => $record->user?->church_id),

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
