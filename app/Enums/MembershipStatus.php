<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle state of a club membership / join application.
 */
enum MembershipStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Localized label (used by Filament selects/badges).
     */
    public function getLabel(): string
    {
        return __("membership.status_labels.{$this->value}");
    }
}
