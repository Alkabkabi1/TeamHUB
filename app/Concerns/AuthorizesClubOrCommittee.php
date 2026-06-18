<?php

namespace App\Concerns;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Committee;

/**
 * Shared authorization for form requests and controllers that manage content
 * (events, news) which may belong either to a club or to a committee within it.
 * When the route resolves a {committee}, the committee-scoped capability is
 * checked; otherwise the club-scoped one. Used so the same EventController /
 * NewsController serve both contexts without duplicating authorization logic.
 */
trait AuthorizesClubOrCommittee
{
    protected function authorizeClubOrCommittee(ClubCapability $clubCapability, CommitteeCapability $committeeCapability): bool
    {
        $committee = $this->route('committee');

        if ($committee instanceof Committee) {
            return $this->user()?->can($committeeCapability->value, $committee) ?? false;
        }

        return $this->user()?->can($clubCapability->value, $this->route('club')) ?? false;
    }
}
