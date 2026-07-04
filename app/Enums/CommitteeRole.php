<?php

namespace App\Enums;

/**
 * Named, committee-scoped roles. A membership may hold several of these; the
 * user's effective capabilities within a committee are the union of their
 * roles' capabilities.
 */
enum CommitteeRole: string
{
    case CommitteeLead = 'committee_lead';
    case ContentManager = 'content_manager';
    case MembershipManager = 'membership_manager';
    case Member = 'member';

    /**
     * @return array<int, CommitteeCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::CommitteeLead => CommitteeCapability::all(),
            self::ContentManager => [CommitteeCapability::ManageNews],
            self::MembershipManager => [CommitteeCapability::ManageMembers, CommitteeCapability::ViewReports],
            self::Member => [],
        };
    }

    public function grants(CommitteeCapability $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function managerRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role->capabilities() !== [],
        ));
    }

    public function isManager(): bool
    {
        return $this->capabilities() !== [];
    }

    /**
     * @return array<int, string>
     */
    public static function managerRoleValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::managerRoles());
    }

    /**
     * @return array<int, string>
     */
    public static function valuesWithCapability(CommitteeCapability $capability): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->grants($capability)),
        ));
    }

    public function label(): string
    {
        return "committee_roles.{$this->value}";
    }
}
