<?php

namespace App\Filament\Resources\AGSUpdates\Pages;

use App\Filament\Resources\AGSUpdates\AGSUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAGSUpdate extends EditRecord
{
    protected static string $resource = AGSUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
