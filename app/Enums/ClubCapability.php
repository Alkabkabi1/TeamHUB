<?php

namespace App\Enums;

/**
 * Fine-grained, club-scoped abilities. Each value is also the name of the
 * authorization Gate ability registered for it (see AuthServiceProvider).
 */
enum ClubCapability: string
{
    case ManageClub = 'manage-club';
    case ManageEvents = 'manage-events';
    case ManageNews = 'manage-news';
    case ManageMembers = 'manage-members';
    case ManageVolunteerHours = 'manage-volunteer-hours';
    case IssueCertificates = 'issue-certificates';
    case ViewReports = 'view-reports';
    case RecordAttendance = 'record-attendance';

    /**
     * All club capabilities.
     *
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * All capability ability names.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $capability): string => $capability->value, self::cases());
    }
}
