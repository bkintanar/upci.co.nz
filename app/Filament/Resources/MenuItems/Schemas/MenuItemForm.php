<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu Item Details')
                    ->schema([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The text that will appear in the menu'),

                        TextInput::make('description')
                            ->maxLength(255)
                            ->helperText('Optional subtitle or description (e.g., "Our history and mission")'),

                        TextInput::make('url')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The URL or path (e.g., /about, /contact, or https://example.com)'),

                        Grid::make(2)
                            ->schema([
                                Select::make('location')
                                    ->required()
                                    ->options([
                                        'header' => 'Header',
                                        'footer' => 'Footer',
                                    ])
                                    ->default('header')
                                    ->native(false),

                                Select::make('parent_id')
                                    ->label('Parent Menu')
                                    ->placeholder('(Top Level)')
                                    ->options(function () {
                                        return MenuItem::query()
                                            ->whereNull('parent_id')
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                return [$item->id => $item->label.' — '.$item->url];
                                            });
                                    })
                                    ->searchable()
                                    ->helperText('Select a parent to create a dropdown menu item')
                                    ->native(false),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower numbers appear first'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('open_in_new_tab')
                                    ->label('Open in New Tab')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
