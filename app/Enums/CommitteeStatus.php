<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Operational state of a committee. "Archived" is not a status — like clubs it
 * is represented by soft-deleting the committee (trashed), so archived
 * committees leave the public listing while preserving all history.
 */
enum CommitteeStatus: string implements HasLabel
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
        return "committees.status_labels.{$this->value}";
    }
}
