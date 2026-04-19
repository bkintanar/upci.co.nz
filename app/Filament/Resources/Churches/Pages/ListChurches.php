<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Enums\UserRole;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Churches\ChurchResource;

class ListChurches extends ListRecords
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $canCreate = $user && UserRole::hasFullAccess($user);

        return $canCreate ? [CreateAction::make()] : [];
    }
}
