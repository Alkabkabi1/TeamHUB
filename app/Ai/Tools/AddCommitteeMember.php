<?php

namespace App\Ai\Tools;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\ClubMembership;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Add an approved club member directly to a committee. Requires ManageMembers capability for the committee.
 */
class AddCommitteeMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Add an approved club member directly to a committee. Requires ManageMembers capability for the committee.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'committee' => $schema->string()
                ->description('Committee name or ID.')
                ->required(),
            'club' => $schema->string()
                ->description('Optional: parent club name or ID.'),
            'user' => $schema->string()
                ->description('The user name or numeric ID to add.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $club = isset($request['club']) ? $this->resolveClub($request['club']) : null;

        $committee = $this->resolveCommittee($request['committee'] ?? null, $club);

        if ($committee === null) {
            return ['error' => 'لم يتم العثور على اللجنة.'];
        }

        if (! Gate::allows(CommitteeCapability::ManageMembers->value, $committee)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذه اللجنة.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على المستخدم. حاول كتابة الاسم كاملاً أو جزءاً منه.'];
        }

        $isClubMember = ClubMembership::where('user_id', $targetUser->id)
            ->where('club_id', $committee->club_id)
            ->where('status', 'approved')
            ->exists();

        if (! $isClubMember) {
            return ['error' => 'يجب أن يكون المستخدم عضوًا في النادي الأب أولاً.'];
        }

        $alreadyMember = CommitteeMembership::where('user_id', $targetUser->id)
            ->where('committee_id', $committee->id)
            ->where('status', 'approved')
            ->exists();

        if ($alreadyMember) {
            return ['error' => 'المستخدم عضو في اللجنة بالفعل.'];
        }

        return [
            'summary' => "إضافة {$targetUser->name} كعضو في لجنة {$committee->name}",
            'changes' => [
                "إنشاء عضوية مقبولة لـ {$targetUser->name} في لجنة {$committee->name}",
            ],
            'params' => [
                'committee_id' => $committee->id,
                'user_id' => $targetUser->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $committee = $this->resolveCommittee($params['committee_id']);

        abort_unless($committee !== null && Gate::allows(CommitteeCapability::ManageMembers->value, $committee), 403);

        $targetUser = User::findOrFail($params['user_id']);

        $membership = CommitteeMembership::updateOrCreate(
            ['user_id' => $targetUser->id, 'committee_id' => $committee->id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncCommitteeRoles([CommitteeRole::Member]);

        return [
            'success' => true,
            'message' => "تمت إضافة {$targetUser->name} كعضو في لجنة {$committee->name}.",
        ];
    }
}
