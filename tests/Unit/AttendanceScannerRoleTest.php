<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\AttendanceCheckin;
use App\Models\EventAttendance;

test('the attendance scanner role grants only the record-attendance capability', function () {
    expect(ClubRole::AttendanceScanner->capabilities())
        ->toBe([ClubCapability::RecordAttendance]);
});

test('the attendance scanner role is a manager role', function () {
    expect(ClubRole::AttendanceScanner->isManager())->toBeTrue();
    expect(ClubRole::managerRoleValues())->toContain('attendance_scanner');
});

test('the club lead role still grants record-attendance', function () {
    expect(ClubRole::ClubLead->grants(ClubCapability::RecordAttendance))->toBeTrue();
});

test('an event attendance has many check-ins', function () {
    expect((new EventAttendance)->checkins()->getRelated())
        ->toBeInstanceOf(AttendanceCheckin::class);
});
