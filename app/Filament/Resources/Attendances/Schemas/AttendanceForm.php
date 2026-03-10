<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Event Block
                Section::make('📋 Event Information')
                    ->description('Quick details about this service or event')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('church_assignment')
                                    ->label('Church Location')
                                    ->state(function () {
                                        $user = Auth::user();

                                        return $user->church ? $user->church->name : 'No church assigned';
                                    })
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('event')
                                    ->label('Service/Event Type')
                                    ->placeholder('Sunday Service, Prayer Meeting, Bible Study...')
                                    ->maxLength(255)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->helperText('What type of service or event is this?'),

                                DatePicker::make('date')
                                    ->label('Service Date')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->default(now())
                                    ->suffixIcon('heroicon-o-calendar-days')
                                    ->helperText('When did this service take place?'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),

                // Attendance Block
                Section::make('👥 Attendance Counter')
                    ->description('Count the people in each category - the total will update automatically')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Adults Row
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('mens')
                                            ->label('Men (Adults)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(1)
                                            ->inputMode('numeric')
                                            ->required()
                                            ->live()
                                            ->prefixIcon('heroicon-o-user')
                                            ->extraInputAttributes(['class' => 'text-center text-lg font-semibold'])
                                            ->helperText('Adult men'),

                                        TextInput::make('ladies')
                                            ->label('Ladies (Adults)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(1)
                                            ->inputMode('numeric')
                                            ->required()
                                            ->live()
                                            ->prefixIcon('heroicon-o-user')
                                            ->extraInputAttributes(['class' => 'text-center text-lg font-semibold'])
                                            ->helperText('Adult women'),
                                    ])
                                    ->columnSpan(2),

                                // Youth & Children Row
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('youth')
                                            ->label('Youth (Teens)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(1)
                                            ->inputMode('numeric')
                                            ->required()
                                            ->live()
                                            ->prefixIcon('heroicon-o-academic-cap')
                                            ->extraInputAttributes(['class' => 'text-center text-lg font-semibold'])
                                            ->helperText('Teenagers'),

                                        TextInput::make('children')
                                            ->label('Children (Kids)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(1)
                                            ->inputMode('numeric')
                                            ->required()
                                            ->live()
                                            ->prefixIcon('heroicon-o-heart')
                                            ->extraInputAttributes(['class' => 'text-center text-lg font-semibold'])
                                            ->helperText('Young children'),
                                    ])
                                    ->columnSpan(2),

                                // Visitors Row
                                TextInput::make('visitors')
                                    ->label('First-Time Visitors')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
                                    ->inputMode('numeric')
                                    ->required()
                                    ->live()
                                    ->prefixIcon('heroicon-o-hand-raised')
                                    ->extraInputAttributes(['class' => 'text-center text-lg font-semibold'])
                                    ->helperText('New faces today')
                                    ->columnSpan(2),
                            ]),

                        // Clean Total Display
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('total_display')
                                    ->label('📊 Total Attendance')
                                    ->content(function ($get) {
                                        $total = ($get('mens') ?? 0) +
                                               ($get('ladies') ?? 0) +
                                               ($get('youth') ?? 0) +
                                               ($get('children') ?? 0) +
                                               ($get('visitors') ?? 0);

                                        return $total.' people';
                                    })
                                    ->extraAttributes([
                                        'class' => 'text-center',
                                        'style' => 'font-size: 2rem; font-weight: bold; color: #1d4ed8; padding: 1rem; background: linear-gradient(to right, #eff6ff, #f0fdf4); border-radius: 0.5rem; border: 1px solid #3b82f6;',
                                    ]),
                            ])
                            ->columnSpanFull(),

                        // Breakdown Display
                        Grid::make(5)
                            ->schema([
                                Placeholder::make('men_breakdown')
                                    ->label('👨 Men')
                                    ->content(fn ($get) => ($get('mens') ?? 0))
                                    ->extraAttributes(['class' => 'text-center text-sm']),

                                Placeholder::make('ladies_breakdown')
                                    ->label('👩 Ladies')
                                    ->content(fn ($get) => ($get('ladies') ?? 0))
                                    ->extraAttributes(['class' => 'text-center text-sm']),

                                Placeholder::make('youth_breakdown')
                                    ->label('🧑‍🎓 Youth')
                                    ->content(fn ($get) => ($get('youth') ?? 0))
                                    ->extraAttributes(['class' => 'text-center text-sm']),

                                Placeholder::make('children_breakdown')
                                    ->label('🧒 Children')
                                    ->content(fn ($get) => ($get('children') ?? 0))
                                    ->extraAttributes(['class' => 'text-center text-sm']),

                                Placeholder::make('visitors_breakdown')
                                    ->label('👋 Visitors')
                                    ->content(fn ($get) => ($get('visitors') ?? 0))
                                    ->extraAttributes(['class' => 'text-center text-sm']),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),

                // Record Information Block
                Section::make('📝 Record Details')
                    ->description('This information will be saved with your attendance record')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user_info')
                                    ->state(function () {
                                        $user = Auth::user();
                                        $role = $user->role ? $user->role->getLabel() : 'Member';

                                        return $user->name.' • '.$role;
                                    })
                                    ->label('Recorded By')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('timestamp_info')
                                    ->state(fn () => now()->format('M j, Y \a\t g:i A'))
                                    ->label('Timestamp')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(true)
                    ->columnSpanFull(),
            ]);
    }
}
