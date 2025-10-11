<?php

namespace App\Filament\Resources\Churches\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ChurchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Information Section
                Section::make('Basic Information')
                    ->description('Essential church details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Church Name')
                            ->size('lg')
                            ->weight('bold')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable()
                                    ->visible(fn ($record) => ! empty($record->email)),

                                TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->copyable()
                                    ->visible(fn ($record) => ! empty($record->phone)),
                            ]),

                        TextEntry::make('website')
                            ->label('Website')
                            ->url(fn ($record) => $record->website)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => ! empty($record->website))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Address Section
                Section::make('Address')
                    ->description('Church location information')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Street Address')
                            ->visible(fn ($record) => ! empty($record->address))
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('city')
                                    ->label('City')
                                    ->visible(fn ($record) => ! empty($record->city)),

                                TextEntry::make('state')
                                    ->label('State/Province')
                                    ->visible(fn ($record) => ! empty($record->state)),

                                TextEntry::make('zip')
                                    ->label('ZIP/Postal Code')
                                    ->visible(fn ($record) => ! empty($record->zip)),
                            ]),

                        TextEntry::make('country')
                            ->label('Country')
                            ->visible(fn ($record) => ! empty($record->country))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record->address) || ! empty($record->city) || ! empty($record->state) || ! empty($record->zip) || ! empty($record->country))
                    ->columnSpanFull(),

                // Social Media Section
                Section::make('Social Media')
                    ->description('Church social media presence')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('facebook')
                                    ->label('Facebook')
                                    ->url(fn ($record) => $record->facebook)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => ! empty($record->facebook)),

                                TextEntry::make('twitter')
                                    ->label('Twitter/X')
                                    ->url(fn ($record) => $record->twitter)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => ! empty($record->twitter)),

                                TextEntry::make('instagram')
                                    ->label('Instagram')
                                    ->url(fn ($record) => $record->instagram)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => ! empty($record->instagram)),

                                TextEntry::make('youtube')
                                    ->label('YouTube')
                                    ->url(fn ($record) => $record->youtube)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => ! empty($record->youtube)),
                            ]),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record->facebook) || ! empty($record->twitter) || ! empty($record->instagram) || ! empty($record->youtube))
                    ->columnSpanFull(),

                // Attendance Statistics Section
                Section::make('Attendance Statistics')
                    ->description('Recent attendance data for this church')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_attendance_records')
                                    ->label('Total Records')
                                    ->getStateUsing(fn ($record) => $record->attendances()->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('latest_attendance')
                                    ->label('Latest Attendance')
                                    ->getStateUsing(function ($record) {
                                        $latest = $record->attendances()->latest()->first();

                                        return $latest ? $latest->total : 0;
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->visible(fn ($record) => $record->attendances()->count() > 0),

                                TextEntry::make('average_attendance')
                                    ->label('Average Attendance')
                                    ->getStateUsing(function ($record) {
                                        $attendances = $record->attendances();
                                        if ($attendances->count() === 0) {
                                            return 0;
                                        }

                                        $total = $attendances->get()->sum(function ($attendance) {
                                            return $attendance->mens + $attendance->ladies + $attendance->youth + $attendance->children + $attendance->visitors;
                                        });

                                        return round($total / $attendances->count());
                                    })
                                    ->badge()
                                    ->color('warning')
                                    ->visible(fn ($record) => $record->attendances()->count() > 0),
                            ]),

                        TextEntry::make('recent_attendance_dates')
                            ->label('Recent Attendance Dates')
                            ->getStateUsing(function ($record) {
                                return $record->attendances()
                                    ->latest()
                                    ->take(5)
                                    ->get()
                                    ->map(function ($attendance) {
                                        return $attendance->date->format('M j').' ('.$attendance->total.')';
                                    })
                                    ->join(' • ');
                            })
                            ->visible(fn ($record) => $record->attendances()->count() > 0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->attendances()->count() > 0)
                    ->columnSpanFull(),

                // Users Section
                Section::make('Assigned Users')
                    ->description('Users assigned to this church')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('users_count')
                                    ->label('Total Users')
                                    ->getStateUsing(fn ($record) => $record->users()->count())
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('pastors_count')
                                    ->label('Pastors & Leaders')
                                    ->getStateUsing(function ($record) {
                                        return $record->users()
                                            ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor', 'elder'])
                                            ->count();
                                    })
                                    ->badge()
                                    ->color('purple')
                                    ->visible(function ($record) {
                                        return $record->users()
                                            ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor', 'elder'])
                                            ->count() > 0;
                                    }),

                                TextEntry::make('staff_count')
                                    ->label('Staff & Deacons')
                                    ->getStateUsing(function ($record) {
                                        return $record->users()
                                            ->whereIn('role', ['deacon', 'usher', 'administrator'])
                                            ->count();
                                    })
                                    ->badge()
                                    ->color('green')
                                    ->visible(function ($record) {
                                        return $record->users()
                                            ->whereIn('role', ['deacon', 'usher', 'administrator'])
                                            ->count() > 0;
                                    }),
                            ]),

                        TextEntry::make('role_breakdown')
                            ->label('Role Breakdown')
                            ->getStateUsing(function ($record) {
                                $roleGroups = $record->users()
                                    ->get()
                                    ->groupBy('role')
                                    ->map(function ($users, $role) {
                                        $roleEnum = \App\Enums\UserRole::tryFrom($role);
                                        $roleName = $roleEnum ? $roleEnum->getLabel() : ucfirst($role);

                                        return $roleName.': '.$users->count();
                                    })
                                    ->values();

                                return $roleGroups->join(' • ');
                            })
                            ->visible(fn ($record) => $record->users()->count() > 0)
                            ->columnSpanFull(),

                        TextEntry::make('users_list')
                            ->label('Recent Users')
                            ->getStateUsing(function ($record) {
                                return $record->users()
                                    ->latest()
                                    ->take(8)
                                    ->get()
                                    ->map(function ($user) {
                                        $roleEnum = $user->role;
                                        $roleName = $roleEnum ? $roleEnum->getLabel() : 'Member';

                                        return $user->name.' ('.$roleName.')';
                                    })
                                    ->join(', ').($record->users()->count() > 8 ? ' ...' : '');
                            })
                            ->visible(fn ($record) => $record->users()->count() > 0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->users()->count() > 0)
                    ->columnSpanFull(),

                // Record Information Section
                Section::make('Record Information')
                    ->description('Information about this church record')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created'),

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
