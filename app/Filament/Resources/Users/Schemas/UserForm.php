<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.users.form.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('admin.users.form.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label(__('admin.users.form.role'))
                    ->options(UserRole::class)
                    ->default(UserRole::Member->value)
                    ->required(),

                // Password: required when creating; on edit it is hidden behind a
                // "change password" toggle and only persisted when actually set.
                Toggle::make('change_password')
                    ->label(__('admin.users.form.change_password'))
                    ->dehydrated(false)
                    ->live()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
                TextInput::make('password')
                    ->label(__('admin.users.form.password'))
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->visible(fn (string $operation, Get $get): bool => $operation === 'create' || (bool) $get('change_password')),
            ]);
    }
}
