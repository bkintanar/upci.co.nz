<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ContactMessages\ContactMessageResource;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;
}
