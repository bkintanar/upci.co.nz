<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Message')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('first_name')
                                ->label('From')
                                ->formatStateUsing(fn ($state, $record) => trim($record->first_name.' '.$record->last_name)),
                            TextEntry::make('email')->copyable(),
                            TextEntry::make('created_at')->label('Received')->dateTime('j M Y, g:ia'),
                        ]),
                        // Escaped by default. Do not add ->html() or swap this for a
                        // markdown/rich renderer — the content comes from an
                        // unauthenticated public endpoint.
                        TextEntry::make('message')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
