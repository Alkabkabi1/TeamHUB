<?php

namespace App\Models;

use App\Enums\CommitteeRole;
use Database\Factories\CommitteeMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CommitteeMembership extends Model
{
    /** @use HasFactory<CommitteeMembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'committee_id',
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
     * @return HasMany<CommitteeMembershipRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(CommitteeMembershipRole::class);
    }

    /**
     * The named committee roles held by this membership.
     *
     * @return Collection<int, CommitteeRole>
     */
    public function committeeRoles(): Collection
    {
        return $this->roles->pluck('role');
    }

    /**
     * Grant a named committee role to this membership (idempotent).
     */
    public function assignCommitteeRole(CommitteeRole $role): void
    {
        $this->roles()->firstOrCreate(['role' => $role->value]);
        $this->unsetRelation('roles');
    }

    /**
     * Replace this membership's roles with exactly the given set.
     *
     * @param  array<int, CommitteeRole>  $roles
     */
    public function syncCommitteeRoles(array $roles): void
    {
        $values = array_values(array_unique(array_map(fn (CommitteeRole $role): string => $role->value, $roles)));

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

    public function hasCommitteeRole(CommitteeRole $role): bool
    {
        return $this->roles()->where('role', $role->value)->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
