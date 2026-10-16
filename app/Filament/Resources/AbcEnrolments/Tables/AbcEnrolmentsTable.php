<?php

namespace App\Filament\Resources\AbcEnrolments\Tables;

use Filament\Tables\Table;
use App\Models\AbcEnrolment;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class AbcEnrolmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Plain TextColumns throughout: every value here came from an
            // unauthenticated public form and must stay escaped -- never
            // ->html(), never a rich/markdown renderer.
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('j M Y, g:ia')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('mobile_phone')
                    ->label('Mobile'),
                TextColumn::make('church_name')
                    ->label('Church')
                    ->formatStateUsing(fn ($state, AbcEnrolment $record) => $record->church_display)
                    ->wrap(),
                TextColumn::make('pastorReference.status_label')
                    ->label("Pastor's reference")
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Not approved' => 'danger',
                        default => 'warning',
                    })
                    ->placeholder('Not requested'),
                IconColumn::make('transcript_path')
                    ->label('Transcript')
                    ->state(fn (AbcEnrolment $record) => filled($record->transcript_path))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('transcript_path')
                    ->label('Transcript')
                    ->nullable()
                    ->trueLabel('With transcript')
                    ->falseLabel('Without transcript'),
                SelectFilter::make('church_name')
                    ->label('Church')
                    ->options(array_combine(AbcEnrolment::CHURCHES, AbcEnrolment::CHURCHES) + ['other' => 'Other']),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('pastorReference'))
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No enrolments yet')
            ->emptyStateDescription('Submissions from /apostolic-bible-college/enrollment/new-student appear here.');
    }
}
