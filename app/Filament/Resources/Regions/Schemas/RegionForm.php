<?php

namespace App\Filament\Resources\Regions\Schemas;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Region')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),

                    // The slug is the wire format the church locator's region
                    // filter sends, so changing it silently breaks any saved
                    // or shared filter URL. National-only by policy, but worth
                    // saying out loud in the UI too.
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Used in URLs and by the church locator filter. Changing it breaks existing links.'),

                    TextInput::make('presbyter_name')
                        ->label('Presbyter')
                        ->maxLength(255)
                        ->placeholder('Not yet appointed')
                        ->helperText('Left blank until the region confirms who to list.'),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Controls the order regions appear in menus and filters.'),
                ])
                ->columns(2),

            Section::make('Landing page')
                ->schema([
                    FileUpload::make('logo_path')
                        ->label('Region Logo')
                        ->helperText('Falls back to the UPCI NZ mark when empty.')
                        ->image()
                        // See DepartmentForm — same disk and SVG constraints.
                        // FILESYSTEM_DISK is `local`, so this must be explicit
                        // or the upload lands in private storage and 404s.
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp'])
                        ->directory('region-logos')
                        ->maxSize(2048),

                    Textarea::make('intro')
                        ->label('Intro message')
                        ->rows(6)
                        ->maxLength(2000)
                        ->placeholder('A short welcome from the region.')
                        ->helperText('Shown at the top of the region landing page.')
                        ->columnSpanFull(),

                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true)
                        ->helperText('Unpublished regions have no landing page. Their churches still appear in the church locator filter.'),
                ]),
        ]);
    }
}
