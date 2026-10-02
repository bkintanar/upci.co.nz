<?php

namespace App\Filament\Resources\Churches\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Churches\Concerns\SyncsLeadership;

class CreateChurch extends CreateRecord
{
    use SyncsLeadership;

    protected static string $resource = ChurchResource::class;

    protected function beforeCreate(): void
    {
        $this->authorizeLeadershipSync(null);
    }

    protected function afterCreate(): void
    {
        $this->applyLeadershipSync($this->record);
    }
}
