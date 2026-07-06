<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Remove a member from a club. Requires ManageMembers capability. Cannot remove the last club lead.
 */
class RemoveWorkspaceMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Remove a member from a workspace. Requires ManageMembers capability. Cannot remove the last workspace lead.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The WorkspaceMembership ID to remove.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = WorkspaceMembership::with('workspace', 'user', 'roles')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على العضوية.'];
        }

        if (! Gate::allows(WorkspaceCapability::ManageMembers->value, $membership->workspace)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذا النادي.'];
        }

        if ($membership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead)) {
            $otherLeadsExist = WorkspaceMembership::where('workspace_id', $membership->workspace_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', WorkspaceRole::WorkspaceLead->value))
                ->exists();

            if (! $otherLeadsExist) {
                return ['error' => 'لا يمكن إزالة قائد النادي الوحيد.'];
            }
        }

        return [
            'summary' => "إزالة {$membership->user->name} من نادي {$membership->workspace->name}",
            'changes' => [
                "حذف عضوية {$membership->user->name} من نادي {$membership->workspace->name}",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = WorkspaceMembership::with('workspace', 'user', 'roles')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(WorkspaceCapability::ManageMembers->value, $membership->workspace), 403);

        if ($membership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead)) {
            $otherLeadsExist = WorkspaceMembership::where('workspace_id', $membership->workspace_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', WorkspaceRole::WorkspaceLead->value))
                ->exists();

            abort_unless($otherLeadsExist, 422);
        }

        $membership->delete();

        return [
            'success' => true,
            'message' => 'تمت إزالة العضو من النادي.',
        ];
    }
}
