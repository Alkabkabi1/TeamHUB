<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\University;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('the user query is scoped to the staff university', function () {
    $mine = University::factory()->create();
    $other = University::factory()->create();
    $staff = User::factory()->universityStaff()->create(['university_id' => $mine->id]);

    $myUser = User::factory()->create(['university_id' => $mine->id]);
    $otherUser = User::factory()->create(['university_id' => $other->id]);

    $this->actingAs($staff);
    $ids = UserResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($myUser->id)
        ->and($ids)->not->toContain($otherUser->id);
});

test('staff create a user scoped to their university, pre-verified, hashed', function () {
    $mine = University::factory()->create();
    $staff = User::factory()->universityStaff()->create(['university_id' => $mine->id]);

    $this->actingAs($staff);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'عضو جديد',
            'email' => 'newcomer@teamhub.test',
            'role' => 'student',
            'password' => 'secret-pass-123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'newcomer@teamhub.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->university_id)->toBe($mine->id)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->role->value)->toBe('student')
        ->and(Hash::check('secret-pass-123', $user->password))->toBeTrue();
});

test('password is required when creating a user', function () {
    $staff = User::factory()->universityStaff()->create(['university_id' => University::factory()->create()->id]);

    $this->actingAs($staff);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'بلا كلمة مرور',
            'email' => 'nopass@teamhub.test',
            'role' => 'student',
            'password' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});
