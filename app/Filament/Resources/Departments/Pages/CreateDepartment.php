<?php

namespace App\Filament\Resources\Departments\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Departments\DepartmentResource;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;
}
