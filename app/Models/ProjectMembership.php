<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProjectMembership extends Model
{
    use HasFactory;

    protected $table = 'project_memberships';

    protected $fillable = [
        'user_id',
        'project_id',
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
     * @return HasMany<ProjectMembershipRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ProjectMembershipRole::class);
    }

    /**
     * The named project roles held by this membership.
     *
     * @return Collection<int, ProjectRole>
     */
    public function projectRoles(): Collection
    {
        return $this->roles->pluck('role');
    }

    /**
     * Grant a named project role to this membership (idempotent).
     */
    public function assignProjectRole(ProjectRole $role): void
    {
        $this->roles()->firstOrCreate(['role' => $role->value]);
        $this->unsetRelation('roles');
    }

    /**
     * Replace this membership's roles with exactly the given set.
     *
     * @param  array<int, ProjectRole>  $roles
     */
    public function syncProjectRoles(array $roles): void
    {
        $values = array_values(array_unique(array_map(fn (ProjectRole $role): string => $role->value, $roles)));

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

    public function hasProjectRole(ProjectRole $role): bool
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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
