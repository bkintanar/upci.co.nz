<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope')
                    ->label('Calendar')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn ($state) => match ($state?->value) {
                        'national' => 'success',
                        'regional' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('region.name')
                    ->label('Region')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->placeholder('—'),
                TextColumn::make('location')->placeholder('—'),
                TextColumn::make('is_published')->badge()->formatStateUsing(fn ($s) => $s ? 'Published' : 'Draft')->color(fn ($s) => $s ? 'success' : 'gray'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
