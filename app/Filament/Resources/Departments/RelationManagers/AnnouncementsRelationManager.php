<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Resources\RelationManagers\RelationManager;

class AnnouncementsRelationManager extends RelationManager
{
    protected static string $relationship = 'announcements';

    protected static ?string $title = 'Announcements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Announcement')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255),
                        MarkdownEditor::make('content')
                            ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'blockquote'])
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            DateTimePicker::make('published_at')
                                ->default(now())
                                ->helperText('A future date schedules the announcement; it stays hidden until then. Leave blank to publish immediately.'),
                            TextInput::make('sort_order')->numeric()->default(0),
                        ]),
                        Toggle::make('is_published')->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable()->placeholder('—'),
                // Three states, not two. Now that published_at actually gates
                // visibility, a scheduled announcement is marked Published but
                // is NOT public yet — showing it as plain "Published" would
                // tell an author it is live when it is not.
                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record) => match (true) {
                        ! $record->is_published => 'Draft',
                        $record->isScheduled() => 'Scheduled',
                        default => 'Published',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Published' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('published_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
