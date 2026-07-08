<?php

namespace App\Models;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Enums\UserRole;
use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'locale'])]
#[Hidden([
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: 'ar';
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'role' => UserRole::class,
    ];

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function workspaceMembership(): HasOne
    {
        return $this->hasOne(WorkspaceMembership::class);
    }

    public function workspace(): HasOneThrough
    {
        return $this->hasOneThrough(
            Workspace::class,
            WorkspaceMembership::class,
            'user_id',
            'id',
            'id',
            'workspace_id',
        );
    }

    public function membershipRequests(): HasMany
    {
        return $this->hasMany(WorkspaceMembershipRequest::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    public function homeUrl(): string
    {
        if ($this->usesMyTasksHome()) {
            return route('my-tasks', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    public function usesMyTasksHome(): bool
    {
        return ! $this->isAdmin()
            && $this->managedWorkspaces()->isEmpty()
            && $this->managedProjects()->isEmpty();
    }

    public function workspaceMembershipFor(Workspace $workspace): ?WorkspaceMembership
    {
        return $this->workspaceMemberships()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->with('roles')
            ->first();
    }

    /**
     * @return Collection<int, WorkspaceCapability>
     */
    public function workspaceCapabilitiesFor(Workspace $workspace): Collection
    {
        $membership = $this->workspaceMembershipFor($workspace);

        if ($membership === null) {
            return collect();
        }

        return $membership->workspaceRoles()
            ->flatMap(fn (WorkspaceRole $role): array => $role->capabilities())
            ->unique()
            ->values();
    }

    public function hasWorkspaceCapability(WorkspaceCapability $capability, Workspace $workspace): bool
    {
        return $this->workspaceCapabilitiesFor($workspace)->contains($capability);
    }

    public function canManageWorkspace(Workspace $workspace): bool
    {
        return $this->isAdmin() || $this->workspaceCapabilitiesFor($workspace)->isNotEmpty();
    }

    /**
     * @return Collection<int, Workspace>
     */
    public function managedWorkspaces(): Collection
    {
        return $this->workspaceMemberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', WorkspaceRole::managerRoleValues()))
            ->with('workspace')
            ->get()
            ->pluck('workspace')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function managedWorkspace(): ?Workspace
    {
        return $this->managedWorkspaces()->first();
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function reviewedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'reviewed_by');
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function taskActivities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function projectMembershipFor(Project $project): ?ProjectMembership
    {
        return $this->projectMemberships()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->with('roles')
            ->first();
    }

    /**
     * @return Collection<int, ProjectCapability>
     */
    public function projectCapabilitiesFor(Project $project): Collection
    {
        $membership = $this->projectMembershipFor($project);

        if ($membership === null) {
            return collect();
        }

        return $membership->projectRoles()
            ->flatMap(fn (ProjectRole $role): array => $role->capabilities())
            ->unique()
            ->values();
    }

    public function hasProjectCapability(ProjectCapability $capability, Project $project): bool
    {
        return $this->projectCapabilitiesFor($project)->contains($capability);
    }

    public function canManageProject(Project $project): bool
    {
        return $this->projectCapabilitiesFor($project)->isNotEmpty();
    }

    /**
     * @return Collection<int, Project>
     */
    public function managedProjects(): Collection
    {
        return $this->projectMemberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', ProjectRole::managerRoleValues()))
            ->whereHas('project.workspace')
            ->with('project')
            ->get()
            ->pluck('project')
            ->filter()
            ->unique('id')
            ->values();
    }
}
