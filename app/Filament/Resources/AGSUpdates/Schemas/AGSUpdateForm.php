<?php

namespace App\Filament\Resources\AGSUpdates\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;

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
                        DateTimePicker::make('published_at'),
                        Toggle::make('is_published')->default(false),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
            ]);
    }
}
