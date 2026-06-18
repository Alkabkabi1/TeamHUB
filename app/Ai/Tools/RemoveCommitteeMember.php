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
 * Remove a member from a committee. Requires ManageMembers capability. Cannot remove the last committee lead.
 */
class RemoveCommitteeMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Remove a member from a committee. Requires ManageMembers capability. Cannot remove the last committee lead.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The CommitteeMembership ID to remove.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = CommitteeMembership::with('committee', 'user', 'roles')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على العضوية.'];
        }

        if (! Gate::allows(CommitteeCapability::ManageMembers->value, $membership->committee)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذه اللجنة.'];
        }

        if ($membership->hasCommitteeRole(CommitteeRole::CommitteeLead)) {
            $otherLeadsExist = CommitteeMembership::where('committee_id', $membership->committee_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', CommitteeRole::CommitteeLead->value))
                ->exists();

            if (! $otherLeadsExist) {
                return ['error' => 'لا يمكن إزالة قائد اللجنة الوحيد.'];
            }
        }

        return [
            'summary' => "إزالة {$membership->user->name} من لجنة {$membership->committee->name}",
            'changes' => [
                "حذف عضوية {$membership->user->name} من لجنة {$membership->committee->name}",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = CommitteeMembership::with('committee', 'user', 'roles')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(CommitteeCapability::ManageMembers->value, $membership->committee), 403);

        if ($membership->hasCommitteeRole(CommitteeRole::CommitteeLead)) {
            $otherLeadsExist = CommitteeMembership::where('committee_id', $membership->committee_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', CommitteeRole::CommitteeLead->value))
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
