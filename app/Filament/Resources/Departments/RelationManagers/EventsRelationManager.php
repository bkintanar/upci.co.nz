<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Calendar';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                        Textarea::make('description')->rows(4),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')->required(),
                            DatePicker::make('end_date'),
                        ]),
                        TextInput::make('location')->maxLength(255),
                        TextInput::make('url')->url()->maxLength(255),
                        Grid::make(2)->schema([
                            Toggle::make('is_published')->default(true),
                            TextInput::make('sort_order')->numeric()->default(0),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->placeholder('—'),
                TextColumn::make('is_published')
                    ->badge()
                    ->formatStateUsing(fn ($s) => $s ? 'Published' : 'Draft')
                    ->color(fn ($s) => $s ? 'success' : 'gray'),
            ])
            ->defaultSort('start_date')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
