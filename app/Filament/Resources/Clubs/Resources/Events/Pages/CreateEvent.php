<?php

namespace App\Filament\Resources\Clubs\Resources\Events\Pages;

use App\Filament\Resources\Clubs\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
