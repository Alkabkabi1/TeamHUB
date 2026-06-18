<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle state of an event. Only {@see self::Active} events are publicly
 * listed and open for registration; drafts are hidden from the public catalog
 * and cancelled events are kept for history.
 */
enum EventStatus: string implements HasLabel
{
    case Active = 'active';
    case Draft = 'draft';
    case Cancelled = 'cancelled';

    /**
     * Human-friendly, localized label.
     */
    public function getLabel(): string
    {
        return __($this->label());
    }

    /**
     * Translation key for this status (reuses the public catalogue labels).
     */
    public function label(): string
    {
        return "events.status_labels.{$this->value}";
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
}
