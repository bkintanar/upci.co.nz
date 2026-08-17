<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('j M Y, g:ia')
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('From')
                    ->formatStateUsing(fn ($state, $record) => trim($record->first_name.' '.$record->last_name))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                // Plain TextColumn: this content is attacker-controlled via an
                // unauthenticated endpoint. It must stay escaped — never ->html(),
                // never a rich/markdown renderer.
                TextColumn::make('message')
                    ->limit(60)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription('Submissions from the contact form on /connect-with-us appear here.');
    }
}
