<?php

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;

test('user certificates are resolved through event attendance', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $attendance = EventAttendance::query()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'attended',
        'checked_in_at' => now(),
    ]);

    $certificate = Certificate::query()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/test.pdf',
    ]);

    expect($user->certificates()->pluck('certificates.id')->all())
        ->toBe([$certificate->id]);
});
