<?php

namespace App\Filament\Resources\AGSUpdates\Pages;

use App\Filament\Resources\AGSUpdates\AGSUpdateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAGSUpdate extends ViewRecord
{
    protected static string $resource = AGSUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
