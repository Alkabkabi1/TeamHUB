<?php

namespace App\Ai\Tools;

use App\Enums\ProjectCapability;
use App\Models\ProjectMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Reject a pending committee join request. Requires ManageMembers capability.
 */
class RejectProjectMembershipRequest extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Reject a pending project membership request. Requires ManageMembers capability.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The ProjectMembership ID to reject.')
                ->required(),
            'reason' => $schema->string()
                ->description('Optional rejection reason.'),
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
            'summary' => "رفض طلب {$membership->user->name} في لجنة {$membership->project->name}",
            'changes' => [
                "رفض طلب {$membership->user->name} في لجنة {$membership->project->name}",
            ],
            'params' => [
                'membership_id' => $membership->id,
                'reason' => trim((string) ($request['reason'] ?? '')),
            ],
        ];
    }

    public function execute(array $params): array
    {
        $membership = ProjectMembership::with('project', 'user')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(ProjectCapability::ManageMembers->value, $membership->project), 403);

        $membership->update([
            'status' => 'rejected',
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
            'rejection_reason' => $params['reason'] ?: null,
        ]);

        return [
            'success' => true,
            'message' => "تم رفض طلب انضمام {$membership->user->name} إلى اللجنة.",
        ];
    }
}
