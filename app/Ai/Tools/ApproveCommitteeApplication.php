<?php

namespace App\Ai\Tools;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\CommitteeMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Approve a pending committee join request. Requires ManageMembers capability for the committee.
 */
class ApproveCommitteeApplication extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Approve a pending committee join request. Requires ManageMembers capability for the committee.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The CommitteeMembership ID to approve.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = CommitteeMembership::with('committee.club', 'user')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على طلب الانضمام.'];
        }

        if ($membership->status !== 'pending') {
            return ['error' => 'هذا الطلب ليس في انتظار المراجعة.'];
        }

        if (! Gate::allows(CommitteeCapability::ManageMembers->value, $membership->committee)) {
            return ['error' => 'ليس لديك صلاحية لمراجعة طلبات هذه اللجنة.'];
        }

        return [
            'summary' => "قبول طلب {$membership->user->name} في لجنة {$membership->committee->name}",
            'changes' => [
                "قبول الطلب وإضافة {$membership->user->name} كعضو في اللجنة",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = CommitteeMembership::with('committee', 'user')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(CommitteeCapability::ManageMembers->value, $membership->committee), 403);

        $membership->update([
            'status' => 'approved',
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
            'rejection_reason' => null,
        ]);

        $membership->assignCommitteeRole(CommitteeRole::Member);

        return [
            'success' => true,
            'message' => "تم قبول انضمام {$membership->user->name} إلى اللجنة.",
        ];
    }
}
