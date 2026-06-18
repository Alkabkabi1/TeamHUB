<?php

use App\Filament\Resources\Clubs\ClubResource;
use App\Filament\Resources\Clubs\Pages\CreateClub;
use App\Filament\Resources\Clubs\Pages\ListClubs;
use App\Models\Club;
use App\Models\University;
use App\Models\User;
use Livewire\Livewire;

function staffOf(University $university): User
{
    return User::factory()->universityStaff()->create(['university_id' => $university->id]);
}

// ─── Panel access ───────────────────────────────────────────────────────────

test('guests are redirected to the filament login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('university staff can access the filament panel', function () {
    $staff = staffOf(University::factory()->create());

    $this->actingAs($staff)->get('/admin')->assertOk();
});

test('students cannot access the filament panel', function () {
    $this->actingAs(User::factory()->student()->create())
        ->get('/admin')
        ->assertForbidden();
});

// ─── Tenancy scoping ──────────────────────────────────────────────────────────

test('the club query is scoped to the staff university', function () {
    $mine = University::factory()->create();
    $other = University::factory()->create();
    $staff = staffOf($mine);

    $myClub = Club::factory()->create(['university_id' => $mine->id]);
    $otherClub = Club::factory()->create(['university_id' => $other->id]);

    $this->actingAs($staff);
    $ids = ClubResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($myClub->id)
        ->and($ids)->not->toContain($otherClub->id);
});

test('the clubs list shows only the staff university clubs', function () {
    $mine = University::factory()->create();
    $other = University::factory()->create();
    $staff = staffOf($mine);

    $myClub = Club::factory()->create(['university_id' => $mine->id]);
    $otherClub = Club::factory()->create(['university_id' => $other->id]);

    $this->actingAs($staff);

    Livewire::test(ListClubs::class)
        ->assertCanSeeTableRecords([$myClub])
        ->assertCanNotSeeTableRecords([$otherClub]);
});

// ─── Create assigns tenancy ─────────────────────────────────────────────────────

test('creating a club assigns it to the staff university', function () {
    $mine = University::factory()->create();
    $staff = staffOf($mine);

    $this->actingAs($staff);

    Livewire::test(CreateClub::class)
        ->fillForm([
            'name' => 'نادي الفلك',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clubs', [
        'name' => 'نادي الفلك',
        'university_id' => $mine->id,
    ]);
});
