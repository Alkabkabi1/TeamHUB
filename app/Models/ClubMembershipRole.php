<?php

namespace App\Models;

use App\Enums\ClubRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMembershipRole extends Model
{
    protected $fillable = [
        'club_membership_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => ClubRole::class,
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ClubMembership::class, 'club_membership_id');
    }
}
