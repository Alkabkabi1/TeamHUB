<?php

namespace App\Enums;

/**
 * Named, club-scoped roles. A membership may hold several of these; the user's
 * effective capabilities within a club are the union of their roles' capabilities.
 */
enum ClubRole: string
{
    case ClubLead = 'club_lead';
    case EventsManager = 'events_manager';
    case ContentManager = 'content_manager';
    case MembershipManager = 'membership_manager';
    case AttendanceScanner = 'attendance_scanner';
    case Member = 'member';

    /**
     * The capabilities granted by this role.
     *
     * @return array<int, ClubCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::ClubLead => ClubCapability::all(),
            self::EventsManager => [
                ClubCapability::ManageEvents,
                ClubCapability::ManageVolunteerHours,
                ClubCapability::IssueCertificates,
                ClubCapability::ViewReports,
            ],
            self::ContentManager => [ClubCapability::ManageNews],
            self::MembershipManager => [ClubCapability::ManageMembers, ClubCapability::ViewReports],
            self::AttendanceScanner => [ClubCapability::RecordAttendance],
            self::Member => [],
        };
    }

    /**
     * Whether this role grants the given capability.
     */
    public function grants(ClubCapability $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * Roles that carry at least one management capability (i.e. club "managers").
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
    public static function valuesWithCapability(ClubCapability $capability): array
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
        return "club_roles.{$this->value}";
    }

    /**
     * Map a legacy `club_memberships.role` string to a named club role.
     */
    public static function fromLegacy(?string $legacy): self
    {
        return match ($legacy) {
            'supervisor', 'club_supervisor' => self::ClubLead,
            'organizer' => self::EventsManager,
            default => self::Member,
        };
    }
}
