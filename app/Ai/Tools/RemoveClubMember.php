<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\ClubMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Remove a member from a club. Requires ManageMembers capability. Cannot remove the last club lead.
 */
class RemoveClubMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Remove a member from a club. Requires ManageMembers capability. Cannot remove the last club lead.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'membership_id' => $schema->integer()
                ->description('The ClubMembership ID to remove.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $membership = ClubMembership::with('club', 'user', 'roles')->find($request['membership_id']);

        if ($membership === null) {
            return ['error' => 'لم يتم العثور على العضوية.'];
        }

        if (! Gate::allows(ClubCapability::ManageMembers->value, $membership->club)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذا النادي.'];
        }

        if ($membership->hasClubRole(ClubRole::ClubLead)) {
            $otherLeadsExist = ClubMembership::where('club_id', $membership->club_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', ClubRole::ClubLead->value))
                ->exists();

            if (! $otherLeadsExist) {
                return ['error' => 'لا يمكن إزالة قائد النادي الوحيد.'];
            }
        }

        return [
            'summary' => "إزالة {$membership->user->name} من نادي {$membership->club->name}",
            'changes' => [
                "حذف عضوية {$membership->user->name} من نادي {$membership->club->name}",
            ],
            'params' => ['membership_id' => $membership->id],
        ];
    }

    public function execute(array $params): array
    {
        $membership = ClubMembership::with('club', 'user', 'roles')->findOrFail($params['membership_id']);

        abort_unless(Gate::allows(ClubCapability::ManageMembers->value, $membership->club), 403);

        if ($membership->hasClubRole(ClubRole::ClubLead)) {
            $otherLeadsExist = ClubMembership::where('club_id', $membership->club_id)
                ->whereKeyNot($membership->id)
                ->whereHas('roles', fn ($q) => $q->where('role', ClubRole::ClubLead->value))
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
