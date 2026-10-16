<?php

namespace App\Filament\Resources\Shared;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * One relation manager shared by Department and Region, mounted on both.
 *
 * Requirement 2 explicitly asks not to build the gallery three times, and that
 * applies to the admin as much as the data model — two near-identical relation
 * managers would drift the moment either is edited. The owner is set implicitly
 * by whichever record you are editing, so there is no owner picker here.
 */
class GalleryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'galleryItems';

    protected static ?string $title = 'Gallery';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gallery item')
                    ->schema([
                        TextInput::make('title')->maxLength(255),
                        Textarea::make('description')->rows(3),
                        // Public disk, jpeg/png/webp only, resized in the
                        // browser: see PhotoUpload for why each rule exists.
                        PhotoUpload::make('image_path', 'gallery')->required(),
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
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->height(48),
                TextColumn::make('title')->searchable()->sortable()->placeholder('Untitled'),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                $this->uploadPhotosAction(),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Many photos at once, each becoming its own gallery item. The action
     * itself lives in UploadPhotosAction, shared with the general gallery's
     * list page; here the owner is always the record being edited.
     */
    protected function uploadPhotosAction(): Action
    {
        return UploadPhotosAction::make(fn () => $this->getOwnerRecord())
            // Custom actions are not authorised automatically in a relation
            // manager; only Create, Edit, Delete and the like are
            // (RelationManager::getDefaultActionAuthorizationResponse). Mirror
            // CreateAction: never on a read-only (View) page, and only when the
            // gallery policy allows create.
            ->authorize(fn (): bool => ! $this->isReadOnly() && $this->canCreate());
    }
}
