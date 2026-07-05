<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A user's genuinely-global, mutually-exclusive identity tier. Workspace-scoped
 * authority is NOT represented here — it lives in workspace memberships/roles.
 */
enum UserRole: string implements HasLabel
{
    case Member = 'member';
    case Admin = 'admin';

    /**
     * Human-friendly, localized label (used by Filament selects/badges).
     */
    public function getLabel(): string
    {
        return __("roles.{$this->value}");
    }

    /**
     * The named route a user of this tier lands on after authenticating,
     * unless a workspace-scoped landing takes precedence (see User::homeUrl()).
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Member => 'dashboard',
            self::Admin => 'filament.admin.pages.dashboard',
        };
    }

    public static function fromLegacy(?string $legacy): self
    {
        return match ($legacy) {
            'student' => self::Member,
            'university_staff' => self::Admin,
            default => self::Member,
        };
    }
}
