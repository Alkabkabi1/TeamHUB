<?php

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Database\QueryException;

test('a user can only have one attendance record per event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventAttendance::query()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    EventAttendance::query()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);
})->throws(QueryException::class);

test('an attendance record can only have one certificate', function () {
    $attendance = EventAttendance::query()->create([
        'user_id' => User::factory()->create()->id,
        'event_id' => Event::factory()->create()->id,
        'status' => 'attended',
    ]);

    Certificate::query()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/first.pdf',
    ]);

    Certificate::query()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/second.pdf',
    ]);
})->throws(QueryException::class);

test('a user can only have one volunteer hour record per event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->past()->create();

    VolunteerHour::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    VolunteerHour::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);
})->throws(QueryException::class);
