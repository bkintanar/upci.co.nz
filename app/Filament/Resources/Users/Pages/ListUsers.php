<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Users\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $canCreate = $user && UserRole::hasFullAccess($user);

        return $canCreate ? [CreateAction::make()] : [];
    }
}
