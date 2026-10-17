<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Region;
use App\Enums\EventScope;
use App\Models\Department;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\Shared\PhotoUpload;
use Filament\Schemas\Components\Utilities\Get;

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
                                    ->helperText('Used in the URL (e.g., /cms/[slug]). Can include slashes for nested URLs like "about/upci"'),
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
                                            ->disk('public')
                                            ->directory('page-images')
                                            ->maxSize(5120),
                                        Select::make('style')
                                            ->label('Style')
                                            ->options([
                                                'gradient-slate' => 'Gradient Slate (Dark)',
                                                'gradient-blue' => 'Gradient Blue',
                                                'gradient-indigo' => 'Gradient Indigo',
                                                'gradient-purple' => 'Gradient Purple',
                                                'solid-blue' => 'Solid Blue',
                                                'solid-indigo' => 'Solid Indigo',
                                            ])
                                            ->default('gradient-slate'),
                                        // 'banner' shows the image whole (for artwork with words in
                                        // it); 'overlay' is the original darkened backdrop. A block
                                        // saved before this field existed renders as 'overlay'.
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options([
                                                'overlay' => 'Text over a darkened image',
                                                'banner' => 'Banner image shown whole',
                                            ])
                                            ->default('overlay')
                                            ->required(),
                                        TextInput::make('image_alt')
                                            ->label('Image description (alt text)')
                                            ->maxLength(255)
                                            ->helperText('For a banner with words in it, type those words.'),
                                        TextInput::make('eyebrow')
                                            ->label('Small label above the heading (banner layout)')
                                            ->maxLength(100),
                                        TextInput::make('verse')
                                            ->label('Verse or line under the subheading (banner layout)')
                                            ->maxLength(255),
                                        TextInput::make('button1_text')
                                            ->label('Primary Button Text')
                                            ->maxLength(50)
                                            ->placeholder('e.g., Learn More'),
                                        TextInput::make('button1_url')
                                            ->label('Primary Button URL')
                                            ->maxLength(255)
                                            ->placeholder('/about/upci'),
                                        TextInput::make('button2_text')
                                            ->label('Secondary Button Text')
                                            ->maxLength(50)
                                            ->placeholder('e.g., Get Involved'),
                                        TextInput::make('button2_url')
                                            ->label('Secondary Button URL')
                                            ->maxLength(255)
                                            ->placeholder('/get-involved'),
                                    ])
                                    ->columns(2),

                                Builder\Block::make('text')
                                    ->label('Text Block')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading (Optional)')
                                            ->maxLength(255),

                                        // Presentation is authored, not inferred. The
                                        // background used to come from the block's ARRAY
                                        // POSITION and the stat styling from the literal
                                        // string "- **", so reordering blocks or writing an
                                        // ordinary bold bullet silently restyled the page.
                                        Select::make('background')
                                            ->label('Background')
                                            ->options(['slate' => 'Light grey', 'white' => 'White'])
                                            ->default('slate')
                                            ->required(),

                                        Select::make('style')
                                            ->label('Presentation')
                                            ->options([
                                                'default' => 'Normal prose',
                                                'stats' => 'Large figures (for bulleted statistics)',
                                            ])
                                            ->default('default')
                                            ->required(),
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
                                            ->disk('public')
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
                                        // The block hard-coded an even split and wrapped the
                                        // right column in a grey panel unconditionally, so it
                                        // could not express an uneven layout and long prose
                                        // ended up boxed whether that suited it or not.
                                        Select::make('ratio')
                                            ->label('Column widths')
                                            ->options([
                                                '1-1' => 'Equal',
                                                '2-1' => 'Wide left, narrow right',
                                                '1-2' => 'Narrow left, wide right',
                                            ])
                                            ->default('1-1')
                                            ->required(),

                                        Toggle::make('right_panel')
                                            ->label('Grey panel on the right column')
                                            ->default(true)
                                            ->helperText('Turn off for plain prose in both columns.'),

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

                                // Text beside a large photo, like the sister site's "About"
                                // section. With no photo yet it shows a designed panel rather
                                // than an empty box or a stock image.
                                Builder\Block::make('feature')
                                    ->label('Text beside a photo')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        MarkdownEditor::make('content')
                                            ->label('Text')
                                            ->columnSpanFull(),
                                        TextInput::make('tagline')
                                            ->label('Closing line, emphasised')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('button_text')
                                            ->label('Button text')
                                            ->maxLength(50),
                                        TextInput::make('button_url')
                                            ->label('Button link')
                                            ->maxLength(255),
                                        FileUpload::make('image')
                                            ->label('Photo')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('page-images')
                                            ->maxSize(5120)
                                            ->helperText('Optional. Until a photo is added, a designed panel is shown.'),
                                        TextInput::make('image_alt')
                                            ->label('Photo description (alt text)')
                                            ->maxLength(255),
                                        TextInput::make('fallback_caption')
                                            ->label('Caption shown until a photo is added')
                                            ->maxLength(255),
                                        Select::make('image_side')
                                            ->label('Photo side')
                                            ->options(['right' => 'Right', 'left' => 'Left'])
                                            ->default('right')
                                            ->required(),
                                        Select::make('background')
                                            ->label('Background')
                                            ->options(['white' => 'White', 'slate' => 'Light grey', 'paper' => 'Paper'])
                                            ->default('white')
                                            ->required(),
                                    ])
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
                                            ->maxLength(255),
                                        // Used by the slim band: a small label ("Next"), a large
                                        // figure ("24–25") and a caption under it ("October").
                                        TextInput::make('eyebrow')
                                            ->label('Small label (band)')
                                            ->maxLength(50),
                                        TextInput::make('highlight')
                                            ->label('Large figure (band)')
                                            ->maxLength(20),
                                        TextInput::make('highlight_caption')
                                            ->label('Caption under the figure (band)')
                                            ->maxLength(50),
                                        Select::make('style')
                                            ->label('Background Style')
                                            ->options([
                                                'blue' => 'Blue',
                                                'indigo' => 'Indigo',
                                                'purple' => 'Purple',
                                                'gray' => 'Gray',
                                                'band' => 'Slim band',
                                                'panel' => 'Split panel',
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

                                        // Column count used to be derived from the NUMBER of
                                        // cards (4 gave two columns, 3 or 5+ gave three), and
                                        // "registration" styling applied whenever every card
                                        // happened to link offsite. Both are now the author's
                                        // choice.
                                        Select::make('columns')
                                            ->label('Columns')
                                            ->options([2 => 'Two', 3 => 'Three', 4 => 'Four'])
                                            ->default(3)
                                            ->required(),

                                        Select::make('background')
                                            ->label('Background')
                                            ->options(['slate' => 'Light grey', 'white' => 'White'])
                                            ->default('slate')
                                            ->required(),

                                        Select::make('style')
                                            ->label('Presentation')
                                            ->options([
                                                'default' => 'Standard cards',
                                                'registration' => 'Registration links (large, opens in a new tab)',
                                            ])
                                            ->default('default')
                                            ->required(),
                                        Builder::make('items')
                                            ->label('Cards')
                                            ->blocks([
                                                Builder\Block::make('card')
                                                    ->schema([
                                                        FileUpload::make('icon')
                                                            ->label('Icon/Image')
                                                            ->image()
                                                            ->disk('public')
                                                            ->directory('page-images')
                                                            ->maxSize(5120),
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
                                                            ->maxLength(255),
                                                        TextInput::make('link_text')
                                                            ->label('Link Text')
                                                            ->maxLength(50),

                                                        // 🔴 These three were rendered by CmsPage.vue but
                                                        // declared NOWHERE. Filament's Builder rebuilds block
                                                        // state from the declared schema on save, so editing an
                                                        // affected card silently dropped them and the card lost
                                                        // its styling for good.
                                                        //
                                                        // A Textarea rather than a Select: icon_svg holds either
                                                        // a style token or raw <svg> markup — CmsPage branches on
                                                        // both — and a Select would coerce any raw markup to null
                                                        // on the next save, which is the very failure this fixes.
                                                        Textarea::make('icon_svg')
                                                            ->label('Icon style or SVG')
                                                            ->rows(2)
                                                            ->helperText('A style token (blue-ministry, green-ministry) or raw <svg> markup. Leave blank for the default treatment.')
                                                            ->columnSpanFull(),

                                                        Select::make('variant')
                                                            ->label('Card type')
                                                            ->options([
                                                                'person' => 'Person (portrait image, opens a detail dialog)',
                                                            ])
                                                            ->placeholder('Standard card')
                                                            ->live()
                                                            ->helperText('Person cards show a portrait crop and open a biography dialog.'),

                                                        Textarea::make('bio')
                                                            ->label('Biography')
                                                            ->rows(5)
                                                            ->maxLength(2000)
                                                            ->visible(fn ($get) => $get('variant') === 'person')
                                                            // Dehydrated so clearing it persists; a hidden
                                                            // Filament field is otherwise omitted from the save
                                                            // and the old value survives.
                                                            ->dehydrated()
                                                            ->helperText('Shown in the dialog when the card is opened. Markdown is supported.')
                                                            ->columnSpanFull(),
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

                                // ---------------------------------------------------------
                                // Data-bound blocks (§9).
                                //
                                // These differ from every block above: the author sets
                                // CONFIGURATION, not content. What renders comes from the
                                // database at request time, so adding a church or publishing
                                // an event updates the page without anyone editing it.
                                //
                                // Each carries its own empty-state message. A section with
                                // nothing in it is a normal state, not a fault, and only the
                                // author knows whether "No events scheduled" or "Dates are
                                // being confirmed" is the true thing to say.
                                // ---------------------------------------------------------

                                Builder\Block::make('church_finder')
                                    ->label('Church Finder')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        TextInput::make('placeholder')
                                            ->label('Search box placeholder')
                                            ->default('Enter your town or suburb')
                                            ->maxLength(255),
                                        TextInput::make('button_text')
                                            ->label('Button text')
                                            ->default('Find a church')
                                            ->maxLength(50),
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options(['band' => 'Band', 'panel' => 'Panel with region links'])
                                            ->default('band')
                                            ->required(),
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->maxLength(255),
                                        Toggle::make('show_regions')
                                            ->label('Show region links (panel layout)')
                                            ->default(true),
                                        Toggle::make('show_directory')
                                            ->label('List the congregations beside it (panel layout)')
                                            ->default(false),
                                        TextInput::make('directory_heading')
                                            ->label('Congregations heading')
                                            ->default('Our congregations')
                                            ->maxLength(255),
                                        TextInput::make('help_heading')
                                            ->label('Help box heading (panel layout)')
                                            ->maxLength(255),
                                        Textarea::make('help_text')
                                            ->label('Help box text')
                                            ->rows(2)
                                            ->maxLength(500),
                                        TextInput::make('help_link_text')
                                            ->label('Help box link text')
                                            ->maxLength(50),
                                        TextInput::make('help_link_url')
                                            ->label('Help box link')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-magnifying-glass'),

                                Builder\Block::make('church_directory')
                                    ->label('Church Directory')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        Select::make('region')
                                            ->label('Region')
                                            ->options(fn () => Region::orderBy('sort_order')->pluck('name', 'slug')->all())
                                            ->placeholder('All regions'),
                                        Toggle::make('group_by_region')
                                            ->label('Group by region')
                                            ->default(true),
                                        Toggle::make('compact')
                                            ->label('Compact list')
                                            ->default(false),
                                        TextInput::make('limit')
                                            ->label('Maximum shown')
                                            ->numeric()
                                            ->helperText('Leave blank for all.'),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('No churches are listed yet.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-building-library'),

                                Builder\Block::make('events_feed')
                                    ->label('Events Feed')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        Select::make('scope')
                                            ->label('Calendar')
                                            ->options(EventScope::options())
                                            ->placeholder('Any'),
                                        Select::make('region')
                                            ->label('Region')
                                            ->options(fn () => Region::orderBy('sort_order')->pluck('name', 'slug')->all())
                                            ->placeholder('Any region'),
                                        Select::make('department')
                                            ->label('Department')
                                            ->options(fn () => Department::orderBy('sort_order')->pluck('name', 'slug')->all())
                                            ->placeholder('Any department'),
                                        TextInput::make('limit')
                                            ->label('Maximum shown')
                                            ->numeric()
                                            ->default(6),
                                        Toggle::make('upcoming_only')
                                            ->label('Upcoming only')
                                            ->default(true)
                                            ->helperText('Hides events whose date has passed.'),
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options(['list' => 'List', 'cards' => 'Cards in a scrolling row'])
                                            ->default('list')
                                            ->required(),
                                        TextInput::make('more_text')
                                            ->label('"More" link text (cards layout)')
                                            ->default('More events')
                                            ->maxLength(50),
                                        TextInput::make('more_url')
                                            ->label('"More" link (cards layout)')
                                            ->default('/events')
                                            ->maxLength(255),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('No events are scheduled at the moment.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-calendar-days'),

                                Builder\Block::make('department_list')
                                    ->label('Department List')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        Toggle::make('show_logos')->label('Show logos')->default(true),
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options(['cards' => 'Cards', 'tiles' => 'Colour tiles'])
                                            ->default('cards')
                                            ->required(),
                                        TextInput::make('lede')
                                            ->label('Introduction line')
                                            ->maxLength(255),
                                        TextInput::make('limit')->label('Maximum shown')->numeric(),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('No departments are published yet.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-rectangle-stack'),

                                Builder\Block::make('region_list')
                                    ->label('Region List')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        Toggle::make('show_logos')->label('Show logos')->default(true),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('No regions are published yet.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-map'),

                                Builder\Block::make('statistics')
                                    ->label('Statistics')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        TextInput::make('lede')->label('Sub-heading')->maxLength(255),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('Figures are being compiled.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-chart-bar'),

                                Builder\Block::make('gallery')
                                    ->label('Gallery')
                                    ->schema([
                                        TextInput::make('heading')->label('Heading')->maxLength(255),
                                        Select::make('owner_type')
                                            ->label('Show images from')
                                            ->options([
                                                'general' => 'The general gallery',
                                                'department' => 'A department',
                                                'region' => 'A region',
                                            ])
                                            ->default('general')
                                            ->live()
                                            ->required(),
                                        Select::make('department')
                                            ->label('Department')
                                            ->options(fn () => Department::orderBy('sort_order')->pluck('name', 'slug')->all())
                                            ->visible(fn ($get) => $get('owner_type') === 'department')
                                            ->dehydrated(),
                                        Select::make('region')
                                            ->label('Region')
                                            ->options(fn () => Region::orderBy('sort_order')->pluck('name', 'slug')->all())
                                            ->visible(fn ($get) => $get('owner_type') === 'region')
                                            ->dehydrated(),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->default('No photos have been added yet.')
                                            ->maxLength(255),
                                    ])
                                    ->icon('heroicon-o-photo'),

                                // The church finder from /find-church: search, filters,
                                // map, list and church details, with the hero above it
                                // (owner request, 2026-10-17: "make this editable in the
                                // CMS"). The hero belongs to the block because its live
                                // church and region counts come from the finder's data.
                                // The churches themselves are edited under Churches.
                                Builder\Block::make('church_locator')
                                    ->label('Church finder (map and list)')
                                    ->schema([
                                        TextInput::make('eyebrow')
                                            ->label('Small label above the heading')
                                            ->default('Find Your Local Church')
                                            ->maxLength(100),
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->default('Discover UPCI Churches')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('heading_highlight')
                                            ->label('Second line of the heading (lighter)')
                                            ->default('Across New Zealand')
                                            ->maxLength(255),
                                        Textarea::make('intro')
                                            ->label('Introduction')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                        Toggle::make('show_stats')
                                            ->label('Show the number of churches and regions')
                                            ->default(true),
                                        TextInput::make('extra_stat_value')
                                            ->label('Third figure (optional)')
                                            ->placeholder('24/7')
                                            ->maxLength(20),
                                        TextInput::make('extra_stat_label')
                                            ->label('Third figure label')
                                            ->placeholder('Directory')
                                            ->maxLength(50),
                                        TextInput::make('search_placeholder')
                                            ->label('Search box hint')
                                            ->default('Search by city, region, church name, or address...')
                                            ->maxLength(255),
                                    ])
                                    // One map per page: the finder keeps its filters in
                                    // the address, and two would fight over it.
                                    ->maxItems(1)
                                    ->icon('heroicon-o-map')
                                    ->columns(2),

                                // The contact form from /connect-with-us, so any page can
                                // carry it (owner choice, 2026-10-17: "Both, via a form
                                // block"). Each message records the page it was sent
                                // from, shown as "Sent from" in Contact Messages.
                                Builder\Block::make('contact_form')
                                    ->label('Contact form')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->default('Get in touch')
                                            ->maxLength(255),
                                        Textarea::make('intro')
                                            ->label('Introduction (optional)')
                                            ->rows(2)
                                            ->maxLength(500),
                                        TextInput::make('button_text')
                                            ->label('Button text')
                                            ->default('Send')
                                            ->maxLength(50),
                                        TextInput::make('success_message')
                                            ->label('Thank-you message')
                                            ->default('Thank you. Your message has been sent.')
                                            ->maxLength(255),
                                    ])
                                    // One form per page: its fields have fixed ids, which
                                    // the error summary links to.
                                    ->maxItems(1)
                                    ->icon('heroicon-o-envelope')
                                    ->columns(1),

                                // Announcements held on the page itself (owner request,
                                // 2026-10-17, for the Apostolic Bible College page),
                                // shown in the same design as a department's. The fields
                                // match a department announcement's, so one component
                                // renders both. Items nest like the Card Grid's cards.
                                Builder\Block::make('announcements')
                                    ->label('Announcements')
                                    ->schema([
                                        TextInput::make('heading')
                                            ->label('Heading')
                                            ->default('Announcements')
                                            ->maxLength(255),
                                        TextInput::make('empty_message')
                                            ->label('If there is nothing to show')
                                            ->helperText('Leave blank to hide the section until there is an announcement.')
                                            ->maxLength(255),
                                        Builder::make('items')
                                            ->label('Announcements')
                                            ->addActionLabel('Add an announcement')
                                            ->blocks([
                                                Builder\Block::make('announcement')
                                                    ->label(fn (?array $state) => filled($state['title'] ?? null) ? $state['title'] : 'Announcement')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpanFull(),
                                                        // A plain date, stored as Y-m-d: it is a label, not
                                                        // a schedule. Newest shows first.
                                                        DatePicker::make('published_at')
                                                            ->label('Date')
                                                            ->format('Y-m-d')
                                                            ->default(fn () => now(config('app.display_timezone'))->toDateString())
                                                            ->required(),
                                                        Toggle::make('is_published')
                                                            ->label('Show on the page')
                                                            ->default(true)
                                                            ->inline(false),
                                                        MarkdownEditor::make('content')
                                                            ->label('Text')
                                                            ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'blockquote'])
                                                            ->columnSpanFull(),
                                                        // The department announcement's photo rules: public
                                                        // disk, jpeg/png/webp, resized in the browser, shown
                                                        // whole (PhotoUpload says why each exists).
                                                        PhotoUpload::make('image_path', 'announcements')
                                                            ->label('Photo')
                                                            ->imageEditor()
                                                            ->helperText('Optional. A flyer or a photo from the event. It shows whole on the page, never cropped.')
                                                            ->columnSpanFull(),
                                                        TextInput::make('image_alt')
                                                            ->label('Photo description')
                                                            ->maxLength(255)
                                                            ->requiredWith('image_path')
                                                            ->validationMessages(['required_with' => 'Describe the photo for people who cannot see it.'])
                                                            ->helperText('Say what the photo shows, for people who cannot see it. For a flyer, include its date and place.')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(2),
                                            ])
                                            // Shown newest first by date on the page, so
                                            // the order here does not matter.
                                            ->collapsible()
                                            ->reorderable(false),
                                    ])
                                    ->icon('heroicon-o-megaphone')
                                    ->columns(1),
                            ])
                            ->collapsible()
                            ->columnSpanFull()
                            ->minItems(1),
                    ]),
            ]);
    }
}
