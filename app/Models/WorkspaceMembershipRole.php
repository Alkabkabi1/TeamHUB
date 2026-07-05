<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceMembershipRole extends Model
{
    protected $table = 'workspace_membership_roles';

    protected $fillable = [
        'workspace_membership_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }

    /**
     * @return BelongsTo<WorkspaceMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMembership::class, 'workspace_membership_id');
    }
}
