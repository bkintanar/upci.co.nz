<?php

namespace App\Filament\Resources\Departments\Schemas;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Components\Utilities\Get;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, $set, ?string $old, ?string $state) {
                                    if (($get('slug') ?? '') !== Str::slug($old)) {
                                        return;
                                    }
                                    $set('slug', Str::slug($state));
                                }),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Used in the URL (e.g., /departments/[slug]).'),
                        ]),
                        MarkdownEditor::make('description')
                            ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'blockquote'])
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Department Logo')
                            ->helperText('Shown in listings and on the department page. Falls back to the UPCI NZ mark when empty.')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp'])
                            ->directory('department-logos')
                            ->maxSize(2048),
                        FileUpload::make('hero_image')
                            ->label('Hero Image')
                            ->image()
                            // See GalleryItemForm — same disk and SVG constraints.
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('department-images')
                            ->maxSize(5120),
                        Grid::make(2)->schema([
                            Select::make('color_theme')
                                ->options([
                                    'blue' => 'Blue',
                                    'green' => 'Green',
                                    'pink' => 'Pink',
                                    'yellow' => 'Yellow',
                                    'purple' => 'Purple',
                                    'indigo' => 'Indigo',
                                ])
                                ->default('blue')
                                ->required(),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ]),
                        Textarea::make('scripture_quote')
                            ->rows(3)
                            ->helperText('Short scripture displayed beside the hero.')
                            ->columnSpanFull(),
                        Toggle::make('is_published')->default(true),
                    ]),
            ]);
    }
}
