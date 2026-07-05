<?php

namespace App\Enums;

/**
 * Named, workspace-scoped roles. A membership may hold several of these; the
 * user's effective capabilities within a workspace are the union of their
 * roles' capabilities.
 */
enum WorkspaceRole: string
{
    case WorkspaceLead = 'workspace_lead';
    case MembershipManager = 'membership_manager';
    case Member = 'member';

    /**
     * @return array<int, WorkspaceCapability>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::WorkspaceLead => WorkspaceCapability::all(),
            self::MembershipManager => [WorkspaceCapability::ManageMembers, WorkspaceCapability::ViewReports],
            self::Member => [],
        };
    }

    public function grants(WorkspaceCapability $capability): bool
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
    public static function valuesWithCapability(WorkspaceCapability $capability): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->grants($capability)),
        ));
    }

    public function label(): string
    {
        return "workspace_roles.{$this->value}";
    }

    public static function fromLegacy(?string $legacy): self
    {
        return match ($legacy) {
            'supervisor', 'club_supervisor', 'club_lead', 'organizer' => self::WorkspaceLead,
            default => self::Member,
        };
    }
}
