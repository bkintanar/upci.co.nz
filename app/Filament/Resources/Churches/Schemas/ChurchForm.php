<?php

namespace App\Filament\Resources\Churches\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Upci\FilamentAddressFinder\Forms\Components\AddressFinder;
use App\Models\User;
use App\Enums\UserRole;

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

                // Church Leadership Section
                Section::make('Church Leadership')
                    ->description('Assign church leadership and board members')
                    ->schema([
                        Select::make('assigned_leadership')
                            ->label('Assign Leadership')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return User::whereIn('role', [
                                    UserRole::PASTOR,
                                    UserRole::SENIOR_PASTOR,
                                    UserRole::ASSISTANT_PASTOR,
                                    UserRole::ELDER,
                                    UserRole::DEACON,
                                    UserRole::USHER,
                                    UserRole::ADMINISTRATOR
                                ])->get()->mapWithKeys(function ($user) {
                                    $roleEnum = $user->role;
                                    $roleName = $roleEnum ? $roleEnum->getLabel() : 'Member';

                                    return [$user->id => $user->name . ' (' . $roleName . ')'];
                                });
                            })
                            ->default([])
                            ->placeholder('Search and select leadership members...')
                            ->helperText('Type to search for leadership members. Each option shows the name and role.')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'min-h-[120px]'])
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $component->state($record->leadership()->pluck('id')->toArray());
                                }
                            })
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    // Get current leadership assigned to this church
                                    $currentLeadership = $record->leadership()->pluck('id')->toArray();

                                    // Users to add (in state but not currently assigned)
                                    $toAdd = array_diff($state ?? [], $currentLeadership);

                                    // Users to remove (currently assigned but not in state)
                                    $toRemove = array_diff($currentLeadership, $state ?? []);

                                    // Add new leadership
                                    if (!empty($toAdd)) {
                                        User::whereIn('id', $toAdd)->update(['church_id' => $record->id]);
                                    }

                                    // Remove leadership (set church_id to null)
                                    if (!empty($toRemove)) {
                                        User::whereIn('id', $toRemove)->update(['church_id' => null]);
                                    }
                                }
                            }),

                        Placeholder::make('current_leadership')
                            ->label('Current Leadership Assigned')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'Save the church first to see assigned leadership.';
                                }

                                $leadership = $record->leadership()->get();

                                if ($leadership->isEmpty()) {
                                    return 'No leadership currently assigned to this church.';
                                }

                                return $leadership->map(function ($member) {
                                    return $member->name . ' (' . $member->role->getLabel() . ')';
                                })->join(', ');
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Church Information Section
                Section::make('Church Information')
                    ->description('Basic church details and settings')
                    ->schema([
                        Textarea::make('description')
                            ->label('Church Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of the church')
                            ->columnSpanFull(),

                        Repeater::make('service_times')
                            ->label('Service Times')
                            ->schema([
                                TextInput::make('service_type')
                                    ->label('Service Type')
                                    ->placeholder('e.g., Worship Service, Bible Study, Prayer Meeting')
                                    ->required(),
                                TimePicker::make('time')
                                    ->label('Time')
                                    ->required(),
                                Select::make('days')
                                    ->label('Days')
                                    ->multiple()
                                    ->options([
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday',
                                    ])
                                    ->required()
                                    ->placeholder('Select days for this service'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Service')
                            ->collapsible()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('is_active')
                                    ->label('Church Status')
                                    ->options([
                                        1 => 'Active',
                                        0 => 'Inactive',
                                    ])
                                    ->default(1)
                                    ->required(),

                                Select::make('is_featured')
                                    ->label('Featured Church')
                                    ->options([
                                        1 => 'Yes',
                                        0 => 'No',
                                    ])
                                    ->default(0),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Address Section
                Section::make('Address')
                    ->description('Church location information')
                    ->schema([
                        AddressFinder::make('address')
                            ->label('Full Address')
                            ->columnSpanFull()
                            ->onAddressSelected(function ($address, Set $set) {
                                $set('address', $address['label']); // Set the main address field
                                $set('street', $address['street'] ?? '');
                                $set('suburb', $address['suburb'] ?? '');
                                $set('city', $address['city']);
                                $set('region', $address['region']);
                                $set('zip', $address['postcode']);
                                $set('country', 'New Zealand');
                                $set('latitude', $address['latitude']);
                                $set('longitude', $address['longitude']);
                            })
                            ->helperText('Start typing to search for New Zealand addresses'),

                        TextInput::make('street')
                            ->label('Street Address')
                            ->maxLength(255)
                            ->readOnly()
                            ->helperText('Auto-populated by address search')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->helperText('Auto-populated by address search'),

                                TextInput::make('region')
                                    ->label('Region')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->helperText('Auto-populated by address search'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('suburb')
                                    ->label('Suburb')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->helperText('Auto-populated by address search'),

                                TextInput::make('zip')
                                    ->label('ZIP/Postal Code')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->helperText('Auto-populated by address search'),
                            ]),

                        TextInput::make('country')
                            ->label('Country')
                            ->maxLength(255)
                            ->default('New Zealand')
                            ->readOnly()
                            ->columnSpanFull(),

                        // Hidden fields for coordinates (auto-populated by address search)
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->hidden(),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->hidden(),
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
