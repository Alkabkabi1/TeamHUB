<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A user's genuinely-global, mutually-exclusive identity tier. Club-scoped
 * authority is NOT represented here — it lives in club memberships/roles.
 */
enum UserRole: string implements HasLabel
{
    case Student = 'student';
    case UniversityStaff = 'university_staff';

    /**
     * Human-friendly, localized label (used by Filament selects/badges).
     */
    public function getLabel(): string
    {
        return __("roles.{$this->value}");
    }

    /**
     * The named route a user of this tier lands on after authenticating,
     * unless a club-scoped landing takes precedence (see User::homeUrl()).
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Student => 'student-dashboard',
            // University staff administer everything in the Filament panel.
            self::UniversityStaff => 'filament.admin.pages.dashboard',
        };
    }
}
