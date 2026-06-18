<?php

namespace App\Filament\Resources\Clubs\Pages;

use App\Filament\Resources\Clubs\ClubResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateClub extends CreateRecord
{
    protected static string $resource = ClubResource::class;

    /**
     * New clubs belong to the creating staff member's university.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['university_id'] = Auth::user()?->university_id;

        return $data;
    }
}
