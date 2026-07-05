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
 * Approve a pending committee join request. Requires ManageMembers capability for the committee.
 */
class ApproveProjectMembershipRequest extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Approve a pending committee join request. Requires ManageMembers capability for the committee.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The ProjectMembership ID to approve.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = ProjectMembership::with('project.workspace', 'user')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على طلب الانضمام.'];
        }

        if ($membership->status !== 'pending') {
            return ['error' => 'هذا الطلب ليس في انتظار المراجعة.'];
        }

        if (! Gate::allows(ProjectCapability::ManageMembers->value, $membership->project)) {
            return ['error' => 'ليس لديك صلاحية لمراجعة طلبات هذه اللجنة.'];
        }

        return [
            'summary' => "قبول طلب {$membership->user->name} في لجنة {$membership->project->name}",
            'changes' => [
                "قبول الطلب وإضافة {$membership->user->name} كعضو في اللجنة",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = ProjectMembership::with('project', 'user')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(ProjectCapability::ManageMembers->value, $membership->project), 403);

        $membership->update([
            'status' => 'approved',
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
            'rejection_reason' => null,
        ]);

        $membership->assignProjectRole(ProjectRole::Member);

        return [
            'success' => true,
            'message' => "تم قبول انضمام {$membership->user->name} إلى اللجنة.",
        ];
    }
}
