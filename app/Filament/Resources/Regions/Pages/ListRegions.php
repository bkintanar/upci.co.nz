<?php

namespace App\Filament\Resources\Regions\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Regions\RegionResource;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    // No CreateAction: the region set is fixed and RegionPolicy::create()
    // is national-only. See RegionResource's class comment.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
