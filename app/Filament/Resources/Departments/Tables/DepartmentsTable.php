<?php

namespace App\Filament\Resources\Departments\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->copyable()->badge()->color('gray'),
                TextColumn::make('announcements_count')->counts('announcements')->label('Announcements'),
                TextColumn::make('events_count')->counts('events')->label('Events'),
                TextColumn::make('is_published')
                    ->badge()
                    ->formatStateUsing(fn ($s) => $s ? 'Published' : 'Draft')
                    ->color(fn ($s) => $s ? 'success' : 'gray'),
                TextColumn::make('updated_at')->dateTime()->sortable()->since(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('is_published')
                    ->label('Status')
                    ->options(['1' => 'Published', '0' => 'Draft']),
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
