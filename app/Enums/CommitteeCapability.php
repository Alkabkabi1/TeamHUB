<?php

namespace App\Enums;

/**
 * Fine-grained, committee-scoped abilities. Each value is also the name of the
 * authorization Gate ability registered for it (see AppServiceProvider). These
 * mirror {@see ClubCapability} but authorize against a Committee rather than a
 * Club, so the two ability sets never collide.
 */
enum CommitteeCapability: string
{
    case ManageCommittee = 'manage-committee';
    case ManageEvents = 'manage-committee-events';
    case ManageNews = 'manage-committee-news';
    case ManageMembers = 'manage-committee-members';
    case ManageVolunteerHours = 'manage-committee-volunteer-hours';
    case IssueCertificates = 'issue-committee-certificates';
    case ViewReports = 'view-committee-reports';

    /**
     * All committee capabilities.
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
