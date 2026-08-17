<?php

namespace App\Filament\Resources\Regions\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Regions\RegionResource;

class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
