<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('event')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total Attendance')
                    ->getStateUsing(fn ($record) => $record->mens + $record->ladies + $record->youth + $record->children + $record->visitors)
                    ->numeric()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw('(mens + ladies + youth + children + visitors) '.$direction);
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('user.name')
                    ->label('Recorded by')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
