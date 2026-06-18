<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Operational state of a club. "Archived" is not a status — it is represented
 * by soft-deleting the club (trashed), so archived clubs leave the public
 * catalogue while preserving all history.
 */
enum ClubStatus: string implements HasLabel
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
     * Translation key for this status (reuses the public catalogue labels).
     */
    public function label(): string
    {
        return "clubs.status_labels.{$this->value}";
    }
}
