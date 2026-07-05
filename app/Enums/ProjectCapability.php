<?php

namespace App\Enums;

/**
 * Fine-grained, project-scoped abilities registered as Gate abilities in
 * AppServiceProvider.
 */
enum ProjectCapability: string
{
    case ManageProject = 'manage-project';
    case ManageUpdates = 'manage-project-updates';
    case ManageMembers = 'manage-project-members';
    case ViewReports = 'view-project-reports';

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
