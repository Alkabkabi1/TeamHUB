<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Add a user directly as an approved club member. Requires ManageMembers capability.
 */
class AddClubMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Add a user directly as an approved club member. Requires ManageMembers capability.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name or ID.')
                ->required(),
            'user' => $schema->string()
                ->description('The user name or numeric ID to add.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! Gate::allows(ClubCapability::ManageMembers->value, $club)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذا النادي.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على المستخدم. حاول كتابة الاسم كاملاً أو جزءاً منه.'];
        }

        if (! $targetUser->isStudent()) {
            return ['error' => 'لا يمكن إضافة هذا المستخدم لأنه ليس طالبًا.'];
        }

        $alreadyMember = ClubMembership::where('user_id', $targetUser->id)
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->exists();

        if ($alreadyMember) {
            return ['error' => 'هذا المستخدم عضو في النادي بالفعل.'];
        }

        return [
            'summary' => "إضافة {$targetUser->name} كعضو في نادي {$club->name}",
            'changes' => [
                "إنشاء عضوية مقبولة لـ {$targetUser->name} في نادي {$club->name}",
            ],
            'params' => [
                'club_id' => $club->id,
                'user_id' => $targetUser->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $club = $this->resolveClub($params['club_id']);

        abort_unless($club !== null && Gate::allows(ClubCapability::ManageMembers->value, $club), 403);

        $targetUser = User::findOrFail($params['user_id']);

        $membership = ClubMembership::updateOrCreate(
            ['user_id' => $targetUser->id, 'club_id' => $club->id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncClubRoles([ClubRole::Member]);

        return [
            'success' => true,
            'message' => "تمت إضافة {$targetUser->name} كعضو في نادي {$club->name}.",
        ];
    }
}
