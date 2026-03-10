<?php

namespace App\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Pages\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View Page')
                ->icon('heroicon-o-eye')
                ->url(fn () => "/{$this->record->slug}", shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->is_published)
                ->color('gray'),
            DeleteAction::make(),
        ];
    }
}
