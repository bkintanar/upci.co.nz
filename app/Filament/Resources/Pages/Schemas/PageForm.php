<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Get;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Page Information')
                    ->description('Basic page settings and metadata')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
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
                                    ->unique(ignorable: fn ($record) => $record)
                                    ->helperText('Used in the URL (e.g., /cms/[slug])'),
                            ]),
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->helperText('SEO meta description (recommended: 150-160 characters)')
                            ->maxLength(255)
                            ->rows(3)
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Only published pages are visible on the frontend'),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Used for ordering pages (lower numbers appear first)'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Page Content')
                    ->description('Add and arrange content blocks to build your page')
                    ->schema([
                        Builder::make('content')
                            ->label('Content Blocks')
                            ->blockNumbers(false)
                            ->addActionLabel('Add Content Block')
                            ->blocks([
                                Builder\Block::make('hero')
                                    ->label('Hero Section')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('subheading')
                                            ->label('Subheading')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                        FileUpload::make('background_image')
                                            ->label('Background Image')
                                            ->image()
                                            ->directory('page-images')
                                            ->maxSize(5120),
                                        Select::make('style')
                                            ->label('Style')
                                            ->options([
                                                'gradient-blue' => 'Gradient Blue',
                                                'gradient-indigo' => 'Gradient Indigo',
                                                'gradient-purple' => 'Gradient Purple',
                                                'solid-blue' => 'Solid Blue',
                                                'solid-indigo' => 'Solid Indigo',
                                            ])
                                            ->default('gradient-blue'),
                                        TextInput::make('button1_text')
                                            ->label('Primary Button Text')
                                            ->maxLength(50)
                                            ->placeholder('e.g., Learn More'),
                                        TextInput::make('button1_url')
                                            ->label('Primary Button URL')
                                            ->maxLength(255)
                                            ->url()
                                            ->placeholder('/about/upci'),
                                        TextInput::make('button2_text')
                                            ->label('Secondary Button Text')
                                            ->maxLength(50)
                                            ->placeholder('e.g., Get Involved'),
                                        TextInput::make('button2_url')
                                            ->label('Secondary Button URL')
                                            ->maxLength(255)
                                            ->url()
                                            ->placeholder('/get-involved'),
                                    ])
                                    ->columns(2),

                                Builder\Block::make('text')
                                    ->label('Text Block')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading (Optional)')
                                            ->maxLength(255),
                                        MarkdownEditor::make('content')
                                            ->label('Content')
                                            ->required()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'heading',
                                                'bulletList',
                                                'orderedList',
                                                'blockquote',
                                            ]),
                                    ])
                                    ->icon('heroicon-o-document-text'),

                                Builder\Block::make('image')
                                    ->label('Image')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Image')
                                            ->required()
                                            ->image()
                                            ->directory('page-images')
                                            ->maxSize(5120),
                                        TextInput::make('alt')
                                            ->label('Alt Text')
                                            ->helperText('Describe the image for accessibility')
                                            ->maxLength(255),
                                        TextInput::make('caption')
                                            ->label('Caption (Optional)')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-photo'),

                                Builder\Block::make('two_column')
                                    ->label('Two Column Layout')
                                    ->schema([
                                        MarkdownEditor::make('left_content')
                                            ->label('Left Column')
                                            ->required()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'heading',
                                                'bulletList',
                                                'orderedList',
                                            ]),
                                        MarkdownEditor::make('right_content')
                                            ->label('Right Column')
                                            ->required()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'heading',
                                                'bulletList',
                                                'orderedList',
                                            ]),
                                    ])
                                    ->icon('heroicon-o-view-columns')
                                    ->columns(2),

                                Builder\Block::make('cta')
                                    ->label('Call to Action')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('text')
                                            ->label('Text')
                                            ->rows(3)
                                            ->maxLength(500),
                                        TextInput::make('button_text')
                                            ->label('Button Text')
                                            ->required()
                                            ->maxLength(50),
                                        TextInput::make('button_url')
                                            ->label('Button URL')
                                            ->required()
                                            ->url()
                                            ->maxLength(255),
                                        Select::make('style')
                                            ->label('Background Style')
                                            ->options([
                                                'blue' => 'Blue',
                                                'indigo' => 'Indigo',
                                                'purple' => 'Purple',
                                                'gray' => 'Gray',
                                            ])
                                            ->default('blue'),
                                    ])
                                    ->icon('heroicon-o-megaphone')
                                    ->columns(2),

                                Builder\Block::make('cards')
                                    ->label('Card Grid')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Section Heading (Optional)')
                                            ->maxLength(255),
                                        Builder::make('items')
                                            ->label('Cards')
                                            ->blocks([
                                                Builder\Block::make('card')
                                                    ->schema([
                                                        FileUpload::make('icon')
                                                            ->label('Icon/Image')
                                                            ->image()
                                                            ->directory('page-images')
                                                            ->maxSize(2048),
                                                        TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->maxLength(100),
                                                        Textarea::make('description')
                                                            ->label('Description')
                                                            ->required()
                                                            ->rows(3)
                                                            ->maxLength(500),
                                                        TextInput::make('link_url')
                                                            ->label('Link URL (Optional)')
                                                            ->url()
                                                            ->maxLength(255),
                                                        TextInput::make('link_text')
                                                            ->label('Link Text')
                                                            ->maxLength(50),
                                                    ])
                                                    ->columns(2),
                                            ])
                                            ->collapsible()
                                            ->minItems(1)
                                            ->maxItems(12),
                                    ])
                                    ->icon('heroicon-o-squares-2x2')
                                    ->columns(1),

                                Builder\Block::make('embed')
                                    ->label('Embed Code')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Title (Optional)')
                                            ->maxLength(255),
                                        Textarea::make('code')
                                            ->label('Embed Code')
                                            ->required()
                                            ->rows(5)
                                            ->helperText('Paste your embed code (e.g., YouTube, Google Maps, etc.)'),
                                    ])
                                    ->icon('heroicon-o-code-bracket'),
                            ])
                            ->collapsible()
                            ->columnSpanFull()
                            ->minItems(1),
                    ]),
            ]);
    }
}
