<?php

namespace App\Enums;

/**
 * Registration/attendance state of a user against an event. The "registered",
 * "approved" and "checked-in" states all count as an occupied seat, while
 * "pending" is awaiting supervisor approval.
 */
enum EventAttendanceStatus: string
{
    case Pending = 'pending';
    case Registered = 'registered';
    case Approved = 'approved';
    case CheckedIn = 'checked_in';

    /**
     * Statuses that occupy a seat (count toward an event's capacity).
     *
     * @return array<int, string>
     */
    public static function registeredValues(): array
    {
        return [
            self::Registered->value,
            self::Approved->value,
            self::CheckedIn->value,
        ];
    }
}
