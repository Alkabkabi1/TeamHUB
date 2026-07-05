<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
