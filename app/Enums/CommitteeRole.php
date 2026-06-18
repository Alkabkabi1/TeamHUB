<?php

namespace App\Enums;

/**
 * Named, committee-scoped roles. A membership may hold several of these; the
 * user's effective capabilities within a committee are the union of their
 * roles' capabilities. Mirrors {@see ClubRole}.
 */
enum CommitteeRole: string
{
    case CommitteeLead = 'committee_lead';
    case EventsManager = 'events_manager';
    case ContentManager = 'content_manager';
    case MembershipManager = 'membership_manager';
    case Member = 'member';

    /**
     * The capabilities granted by this role.
     *
     * @return array<int, CommitteeCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::CommitteeLead => CommitteeCapability::all(),
            self::EventsManager => [
                CommitteeCapability::ManageEvents,
                CommitteeCapability::ManageVolunteerHours,
                CommitteeCapability::IssueCertificates,
                CommitteeCapability::ViewReports,
            ],
            self::ContentManager => [CommitteeCapability::ManageNews],
            self::MembershipManager => [CommitteeCapability::ManageMembers, CommitteeCapability::ViewReports],
            self::Member => [],
        };
    }

    /**
     * Whether this role grants the given capability.
     */
    public function grants(CommitteeCapability $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * Roles that carry at least one management capability (i.e. committee "managers").
     *
     * @return array<int, self>
     */
    public static function managerRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role->capabilities() !== [],
        ));
    }

    /**
     * Whether this role is a management role (carries any capability).
     */
    public function isManager(): bool
    {
        return $this->capabilities() !== [];
    }

    /**
     * The string values of all management roles.
     *
     * @return array<int, string>
     */
    public static function managerRoleValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::managerRoles());
    }

    /**
     * The string values of every role that grants the given capability.
     *
     * @return array<int, string>
     */
    public static function valuesWithCapability(CommitteeCapability $capability): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->grants($capability)),
        ));
    }

    /**
     * Human-friendly translation key for this role.
     */
    public function label(): string
    {
        return "committee_roles.{$this->value}";
    }
}
