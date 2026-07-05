<?php

namespace App\Ai\Tools;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Remove a member from a committee. Requires ManageMembers capability. Cannot remove the last committee lead.
 */
class RemoveProjectMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Remove a member from a committee. Requires ManageMembers capability. Cannot remove the last committee lead.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The ProjectMembership ID to remove.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = ProjectMembership::with('project', 'user', 'roles')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على العضوية.'];
        }

        if (! Gate::allows(ProjectCapability::ManageMembers->value, $membership->project)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذه اللجنة.'];
        }

        if ($membership->hasProjectRole(ProjectRole::ProjectLead)) {
            $otherLeadsExist = ProjectMembership::where('project_id', $membership->project_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', ProjectRole::ProjectLead->value))
                ->exists();

            if (! $otherLeadsExist) {
                return ['error' => 'لا يمكن إزالة قائد اللجنة الوحيد.'];
            }
        }

        return [
            'summary' => "إزالة {$membership->user->name} من لجنة {$membership->project->name}",
            'changes' => [
                "حذف عضوية {$membership->user->name} من لجنة {$membership->project->name}",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = ProjectMembership::with('project', 'user', 'roles')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(ProjectCapability::ManageMembers->value, $membership->project), 403);

        if ($membership->hasProjectRole(ProjectRole::ProjectLead)) {
            $otherLeadsExist = ProjectMembership::where('project_id', $membership->project_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', ProjectRole::ProjectLead->value))
                ->exists();

            abort_unless($otherLeadsExist, 422);
        }

        $membership->delete();

        return [
            'success' => true,
            'message' => 'تمت إزالة العضو من اللجنة.',
        ];
    }
}
