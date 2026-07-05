<?php

namespace App\Enums;

/**
 * Named, project-scoped roles. A membership may hold several of these; the
 * user's effective capabilities within a project are the union of their
 * roles' capabilities.
 */
enum ProjectRole: string
{
    case ProjectLead = 'project_lead';
    case ContentManager = 'content_manager';
    case MembershipManager = 'membership_manager';
    case Member = 'member';

    /**
     * @return array<int, ProjectCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::ProjectLead => ProjectCapability::all(),
            self::ContentManager => [ProjectCapability::ManageUpdates],
            self::MembershipManager => [ProjectCapability::ManageMembers, ProjectCapability::ViewReports],
            self::Member => [],
        };
    }

    public function grants(ProjectCapability $capability): bool
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
    public static function valuesWithCapability(ProjectCapability $capability): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->grants($capability)),
        ));
    }

    public function label(): string
    {
        return "project_roles.{$this->value}";
    }

    public static function fromLegacy(?string $legacy): self
    {
        return match ($legacy) {
            'committee_lead' => self::ProjectLead,
            default => self::Member,
        };
    }
}
