<?php

namespace App\Enums;

/**
 * Fine-grained, committee-scoped abilities registered as Gate abilities in
 * AppServiceProvider.
 */
enum CommitteeCapability: string
{
    case ManageCommittee = 'manage-committee';
    case ManageNews = 'manage-committee-news';
    case ManageMembers = 'manage-committee-members';
    case ViewReports = 'view-committee-reports';

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $capability): string => $capability->value, self::cases());
    }
}
