<?php

namespace App\Filament\Resources\Churches\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class ChurchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Information Section
                Section::make('Basic Information')
                    ->description('Essential church details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Church Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255),
                            ]),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Address Section
                Section::make('Address')
                    ->description('Church location information')
                    ->schema([
                        TextInput::make('address')
                            ->label('Street Address')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255),

                                TextInput::make('state')
                                    ->label('State/Province')
                                    ->maxLength(255),

                                TextInput::make('zip')
                                    ->label('ZIP/Postal Code')
                                    ->maxLength(255),
                            ]),

                        TextInput::make('country')
                            ->label('Country')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Social Media Section
                Section::make('Social Media')
                    ->description('Church social media presence')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('facebook')
                                    ->label('Facebook')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://facebook.com/yourchurch'),

                                TextInput::make('twitter')
                                    ->label('Twitter/X')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://twitter.com/yourchurch'),

                                TextInput::make('instagram')
                                    ->label('Instagram')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://instagram.com/yourchurch'),

                                TextInput::make('youtube')
                                    ->label('YouTube')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://youtube.com/@yourchurch'),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
