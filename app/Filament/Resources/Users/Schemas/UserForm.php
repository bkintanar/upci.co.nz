<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Information Section
                Section::make('User Information')
                    ->description('Basic user account details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Access & Assignment Section
                Section::make('Access & Assignment')
                    ->description('Access level, church, and optional regional assignment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('access_level')
                                    ->label('Access Level')
                                    ->options(AccessLevel::getOptions())
                                    ->placeholder('Select access level')
                                    ->helperText('Local: one church. Regional: one region. National: full access.')
                                    ->live()
                                    // Privilege fields: only a national user may change these.
                                    // UserPolicy::update() returns true unconditionally for self-edit,
                                    // so without this any panel user could promote themselves.
                                    // disabled() alone is NOT enough — Filament re-hydrates disabled
                                    // fields unless dehydrated() also returns false.
                                    ->disabled(fn () => ! auth()->user()?->isNational())
                                    ->dehydrated(fn () => (bool) auth()->user()?->isNational()),

                                Select::make('role')
                                    ->label('Role')
                                    ->options(UserRole::getOptions())
                                    ->default(UserRole::MEMBER)
                                    ->required()
                                    ->searchable()
                                    // Privilege fields: only a national user may change these.
                                    // UserPolicy::update() returns true unconditionally for self-edit,
                                    // so without this any panel user could promote themselves.
                                    // disabled() alone is NOT enough — Filament re-hydrates disabled
                                    // fields unless dehydrated() also returns false.
                                    ->disabled(fn () => ! auth()->user()?->isNational())
                                    ->dehydrated(fn () => (bool) auth()->user()?->isNational()),

                                Select::make('church_id')
                                    ->label('Church')
                                    ->relationship('church', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Select a church')
                                    ->requiredIf('access_level', AccessLevel::LOCAL->value)
                                    // Privilege fields: only a national user may change these.
                                    // UserPolicy::update() returns true unconditionally for self-edit,
                                    // so without this any panel user could promote themselves.
                                    // disabled() alone is NOT enough — Filament re-hydrates disabled
                                    // fields unless dehydrated() also returns false.
                                    ->disabled(fn () => ! auth()->user()?->isNational())
                                    ->dehydrated(fn () => (bool) auth()->user()?->isNational()),

                                Select::make('region_id')
                                    ->label('Assigned Region')
                                    ->relationship('region', 'name')
                                    ->preload()
                                    ->placeholder('Select region')
                                    ->visible(fn ($get) => $get('access_level') === AccessLevel::REGIONAL->value)
                                    ->requiredIf('access_level', AccessLevel::REGIONAL->value)
                                    // Privilege fields: only a national user may change these.
                                    // UserPolicy::update() returns true unconditionally for self-edit,
                                    // so without this any panel user could promote themselves.
                                    // disabled() alone is NOT enough — Filament re-hydrates disabled
                                    // fields unless dehydrated() also returns false.
                                    ->disabled(fn () => ! auth()->user()?->isNational())
                                    ->dehydrated(fn () => (bool) auth()->user()?->isNational()),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
