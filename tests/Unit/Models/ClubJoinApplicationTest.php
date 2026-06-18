<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('club join application belongs to club user and reviewer', function () {
    $application = ClubJoinApplication::factory()->create();

    expect($application->club())->toBeInstanceOf(BelongsTo::class)
        ->and($application->club->id)->toBe($application->club_id)
        ->and($application->user())->toBeInstanceOf(BelongsTo::class)
        ->and($application->user->id)->toBe($application->user_id);
});

test('club join application casts attributes correctly', function () {
    $reviewer = User::factory()->create();
    $reviewedAt = now()->startOfSecond();

    $application = ClubJoinApplication::factory()->approved()->create([
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => $reviewedAt,
        'weekly_hours' => 6,
    ]);

    expect($application->weekly_hours)->toBeInt()->toBe(6)
        ->and($application->reviewed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($application->reviewed_at->equalTo($reviewedAt))->toBeTrue()
        ->and($application->reviewer->id)->toBe($reviewer->id);
});

test('pending factory state keeps application awaiting review', function () {
    $application = ClubJoinApplication::factory()->pending()->create();

    expect($application->status)->toBe('pending')
        ->and($application->reviewed_at)->toBeNull()
        ->and($application->reviewed_by)->toBeNull();
});

test('approved factory state records review metadata', function () {
    $application = ClubJoinApplication::factory()->approved()->create();

    expect($application->status)->toBe('approved')
        ->and($application->reviewed_at)->not->toBeNull();
});

test('rejected factory state records review metadata', function () {
    $reviewer = User::factory()->clubSupervisor()->create();
    $reviewedAt = now()->startOfSecond();

    $application = ClubJoinApplication::factory()->rejected()->create([
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => $reviewedAt,
    ]);

    expect($application->status)->toBe('rejected')
        ->and($application->reviewed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($application->reviewed_at->equalTo($reviewedAt))->toBeTrue()
        ->and($application->reviewer->id)->toBe($reviewer->id);
});

test('club join application is mass assignable', function () {
    $club = Club::factory()->create();
    $user = User::factory()->create();

    $application = ClubJoinApplication::create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'full_name' => 'وئام راشد',
        'university_email' => $user->email,
        'phone' => '0500000000',
        'level' => 'المستوى العاشر',
        'major' => 'هندسة البرمجيات',
        'skills' => 'برمجة',
        'weekly_hours' => 4,
        'tools' => 'VS Code',
        'motivation' => 'التطوع',
        'contribution' => 'الفعاليات',
        'status' => 'pending',
    ]);

    expect($application->fresh())
        ->full_name->toBe('وئام راشد')
        ->major->toBe('هندسة البرمجيات')
        ->status->toBe('pending');
});
