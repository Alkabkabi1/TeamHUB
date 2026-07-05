<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('the user query returns all users for admins', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->member()->create();
    $other = User::factory()->member()->create();

    $this->actingAs($admin);
    $ids = UserResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($member->id)
        ->and($ids)->toContain($other->id);
});

test('admins create a user pre-verified with hashed password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'عضو جديد',
            'email' => 'newcomer@teamhub.test',
            'role' => 'member',
            'password' => 'secret-pass-123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'newcomer@teamhub.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->role->value)->toBe('member')
        ->and(Hash::check('secret-pass-123', $user->password))->toBeTrue();
});

test('password is required when creating a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'بلا كلمة مرور',
            'email' => 'nopass@teamhub.test',
            'role' => 'member',
            'password' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});
