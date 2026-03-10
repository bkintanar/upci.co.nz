<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // User Information Section
                Section::make('User Information')
                    ->description('Basic user account details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable(),
                            ]),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime()
                            ->placeholder('Not verified')
                            ->visible(fn ($record) => $record->email_verified_at)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Church Assignment Section
                Section::make('Church Assignment')
                    ->description('Church membership and role information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('church.name')
                                    ->label('Church')
                                    ->placeholder('No church assigned')
                                    ->visible(fn ($record) => $record->church_id),

                                TextEntry::make('role')
                                    ->label('Role')
                                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'Member')
                                    ->badge()
                                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Activity Section
                Section::make('Activity')
                    ->description('User activity and statistics')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('attendances_count')
                                    ->label('Attendance Records')
                                    ->getStateUsing(fn ($record) => $record->attendances()->count())
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('last_attendance_date')
                                    ->label('Last Attendance Entry')
                                    ->getStateUsing(function ($record) {
                                        $lastAttendance = $record->attendances()->latest()->first();

                                        return $lastAttendance ? $lastAttendance->date->format('M j, Y') : 'No records';
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Record Information Section
                Section::make('Account Information')
                    ->description('Account creation and update details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Account Created'),

                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated'),
                            ]),
                    ])
                    ->collapsible(false)
                    ->columnSpanFull(),
            ]);
    }
}
