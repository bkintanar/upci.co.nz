<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Church;
use App\Enums\UserRole;
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

                // Church Assignment Section
                Section::make('Church Assignment')
                    ->description('Assign user to a church and role')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('church_id')
                                    ->label('Church')
                                    ->relationship('church', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Select a church (optional)'),

                                Select::make('role')
                                    ->label('Role')
                                    ->options(UserRole::getOptions())
                                    ->default(UserRole::MEMBER)
                                    ->required()
                                    ->searchable(),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
