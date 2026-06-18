<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Staff-created users belong to the staff member's university.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['university_id'] = Auth::user()?->university_id;

        return $data;
    }

    /**
     * Pre-verify staff-created accounts (they are created by an administrator,
     * not self-registered). email_verified_at is guarded, so set it directly.
     */
    protected function afterCreate(): void
    {
        if ($this->record->email_verified_at === null) {
            $this->record->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
