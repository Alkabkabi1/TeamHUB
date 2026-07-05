<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class WorkspaceMembership extends Model
{
    use HasFactory;

    protected $table = 'workspace_memberships';

    protected $fillable = [
        'user_id',
        'workspace_id',
        'status',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<WorkspaceMembershipRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(WorkspaceMembershipRole::class);
    }

    /**
     * The named workspace roles held by this membership.
     *
     * @return Collection<int, WorkspaceRole>
     */
    public function workspaceRoles(): Collection
    {
        return $this->roles->pluck('role');
    }

    /**
     * Grant a named workspace role to this membership (idempotent).
     */
    public function assignWorkspaceRole(WorkspaceRole $role): void
    {
        $this->roles()->firstOrCreate(['role' => $role->value]);
        $this->unsetRelation('roles');
    }

    /**
     * Replace this membership's roles with exactly the given set.
     *
     * @param  array<int, WorkspaceRole>  $roles
     */
    public function syncWorkspaceRoles(array $roles): void
    {
        $values = array_values(array_unique(array_map(fn (WorkspaceRole $role): string => $role->value, $roles)));

        if ($values === []) {
            $this->roles()->delete();
            $this->unsetRelation('roles');

            return;
        }

        $this->roles()->whereNotIn('role', $values)->delete();

        foreach ($roles as $role) {
            $this->roles()->firstOrCreate(['role' => $role->value]);
        }

        $this->unsetRelation('roles');
    }

    public function hasWorkspaceRole(WorkspaceRole $role): bool
    {
        return $this->roles()->where('role', $role->value)->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
