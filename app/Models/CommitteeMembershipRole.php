<?php

namespace App\Models;

use App\Enums\CommitteeRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMembershipRole extends Model
{
    protected $fillable = [
        'committee_membership_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => CommitteeRole::class,
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(CommitteeMembership::class, 'committee_membership_id');
    }
}
