<?php

namespace App\Filament\Resources\AGSUpdates\Pages;

use App\Filament\Resources\AGSUpdates\AGSUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAGSUpdates extends ListRecords
{
    protected static string $resource = AGSUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
