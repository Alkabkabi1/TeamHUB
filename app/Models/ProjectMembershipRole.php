<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMembershipRole extends Model
{
    protected $table = 'project_membership_roles';

    protected $fillable = [
        'project_membership_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProjectRole::class,
        ];
    }

    /**
     * @return BelongsTo<ProjectMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(ProjectMembership::class, 'project_membership_id');
    }
}
