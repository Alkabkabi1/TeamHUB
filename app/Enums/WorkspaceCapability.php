<?php

namespace App\Enums;

/**
 * Fine-grained, workspace-scoped abilities. Each value is also the name of the
 * authorization Gate ability registered for it (see AppServiceProvider).
 */
enum WorkspaceCapability: string
{
    case ManageWorkspace = 'manage-workspace';
    case ManageMembers = 'manage-members';
    case ViewReports = 'view-reports';

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
