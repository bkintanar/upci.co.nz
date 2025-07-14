<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }
}
