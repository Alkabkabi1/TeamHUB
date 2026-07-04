<?php

namespace App\Enums;

/**
 * Named, club-scoped roles. A membership may hold several of these; the user's
 * effective capabilities within a club are the union of their roles' capabilities.
 */
enum ClubRole: string
{
    case ClubLead = 'club_lead';
    case MembershipManager = 'membership_manager';
    case Member = 'member';

    /**
     * @return array<int, ClubCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::ClubLead => ClubCapability::all(),
            self::MembershipManager => [ClubCapability::ManageMembers, ClubCapability::ViewReports],
            self::Member => [],
        };
    }

    public function grants(ClubCapability $capability): bool
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
    public static function valuesWithCapability(ClubCapability $capability): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->grants($capability)),
        ));
    }

    public function label(): string
    {
        return "club_roles.{$this->value}";
    }

    public static function fromLegacy(?string $legacy): self
    {
        return match ($legacy) {
            'supervisor', 'club_supervisor', 'organizer' => self::ClubLead,
            default => self::Member,
        };
    }
}
