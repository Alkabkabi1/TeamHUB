<?php

namespace App\Models;

use App\Enums\ClubRole;
use Database\Factories\ClubMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ClubMembership extends Model
{
    /** @use HasFactory<ClubMembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
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
     * @return HasMany<ClubMembershipRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ClubMembershipRole::class);
    }

    /**
     * The named club roles held by this membership.
     *
     * @return Collection<int, ClubRole>
     */
    public function clubRoles(): Collection
    {
        return $this->roles->pluck('role');
    }

    /**
     * Grant a named club role to this membership (idempotent).
     */
    public function assignClubRole(ClubRole $role): void
    {
        $this->roles()->firstOrCreate(['role' => $role->value]);
        $this->unsetRelation('roles');
    }

    /**
     * Replace this membership's roles with exactly the given set.
     *
     * @param  array<int, ClubRole>  $roles
     */
    public function syncClubRoles(array $roles): void
    {
        $values = array_values(array_unique(array_map(fn (ClubRole $role): string => $role->value, $roles)));

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

    public function hasClubRole(ClubRole $role): bool
    {
        return $this->roles()->where('role', $role->value)->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
