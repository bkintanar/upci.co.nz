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

                TextColumn::make('church.name')
                    ->label('Church')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No church')
                    ->toggleable(),

                TextColumn::make('event')
                    ->searchable()
                    ->placeholder('No event specified'),

                TextColumn::make('total')
                    ->label('Total Attendance')
                    ->getStateUsing(fn ($record) => $record->mens + $record->ladies + $record->youth + $record->children + $record->visitors)
                    ->numeric()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw('(mens + ladies + youth + children + visitors) '.$direction);
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('breakdown')
                    ->label('Breakdown')
                    ->getStateUsing(function ($record) {
                        $parts = [];
                        if ($record->mens > 0) {
                            $parts[] = "M:{$record->mens}";
                        }
                        if ($record->ladies > 0) {
                            $parts[] = "L:{$record->ladies}";
                        }
                        if ($record->youth > 0) {
                            $parts[] = "Y:{$record->youth}";
                        }
                        if ($record->children > 0) {
                            $parts[] = "C:{$record->children}";
                        }
                        if ($record->visitors > 0) {
                            $parts[] = "V:{$record->visitors}";
                        }

                        return implode(' • ', $parts);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Recorded by')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.role')
                    ->label('Role')
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'Member')
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->toggleable(),

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
