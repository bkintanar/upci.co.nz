<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use App\Models\SiteSetting;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;

/**
 * Site-wide settings.
 *
 * 🔴 A custom Filament Page has no model and never consults a policy —
 * CanAuthorizeAccess::canAccess() hard-returns true, and AdminPanelProvider
 * calls discoverPages(), so without the override below this page would appear
 * for every panel user and let a local church admin replace the site logo.
 */
class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Settings';

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.manage-site-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isNational() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Logos')
                    ->description('The header and footer use different lockups. Leave either empty to fall back to the bundled default.')
                    ->schema([
                        FileUpload::make('header_logo_path')
                            ->label('Header logo')
                            ->helperText('Shown in the navbar. The stacked lockup suits this space.')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/webp', 'image/svg+xml'])
                            ->directory('site')
                            ->maxSize(2048),
                        FileUpload::make('footer_logo_path')
                            ->label('Footer logo')
                            ->helperText('Shown in the footer, which has room for the wide horizontal lockup.')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/webp', 'image/svg+xml'])
                            ->directory('site')
                            ->maxSize(2048),
                    ])
                    ->columns(2),

                Section::make('Contact & footer')
                    ->schema([
                        TextInput::make('contact_email')->email()->maxLength(255),
                        TextInput::make('footer_blurb')->label('Footer blurb')->maxLength(500),
                    ])
                    ->columns(2),

                Section::make('Social links')
                    ->description('Only the platforms listed here are rendered. Remove a row to remove the icon.')
                    ->schema([
                        Repeater::make('social_links')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                        'tiktok' => 'TikTok',
                                    ])
                                    ->required(),
                                TextInput::make('url')->url()->required()->maxLength(255),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add a social link')
                            ->default([]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()->success()->title('Site settings saved')->send();
    }
}
