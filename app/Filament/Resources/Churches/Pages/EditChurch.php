<?php

namespace App\Filament\Resources\Churches\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Churches\Concerns\SyncsLeadership;

class EditChurch extends EditRecord
{
    use SyncsLeadership;

    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->authorizeLeadershipSync($this->record);
    }

    protected function afterSave(): void
    {
        $this->applyLeadershipSync($this->record);
    }
}
