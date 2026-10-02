<?php

namespace App\Filament\Resources\AGSUpdates\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;

class AGSUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Update')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255),
                        Textarea::make('content')->rows(8),
                        DateTimePicker::make('published_at')
                            ->helperText('Leave empty to publish immediately. A future date schedules the update to go live then.'),
                        Toggle::make('is_published')->default(false),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
            ]);
    }
}
