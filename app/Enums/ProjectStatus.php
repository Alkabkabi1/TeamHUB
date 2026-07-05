<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Operational state of a project. "Archived" is not a status — like workspaces
 * it is represented by soft-deleting the project (trashed), so archived
 * projects leave the public listing while preserving all history.
 */
enum ProjectStatus: string implements HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Founding = 'founding';

    /**
     * Human-friendly, localized label (used by Filament selects/badges).
     */
    public function getLabel(): string
    {
        return __($this->label());
    }

    /**
     * All status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * Translation key for this status.
     */
    public function label(): string
    {
        return "projects.status_labels.{$this->value}";
    }
}
