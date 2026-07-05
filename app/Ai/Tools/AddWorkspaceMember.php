<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Add a user directly as an approved club member. Requires ManageMembers capability.
 */
class AddWorkspaceMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Add a user directly as an approved club member. Requires ManageMembers capability.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->string()
                ->description('The workspace name or ID.')
                ->required(),
            'user' => $schema->string()
                ->description('The user name or numeric ID to add.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? null);

        if ($workspace === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! Gate::allows(WorkspaceCapability::ManageMembers->value, $workspace)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذا النادي.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على المستخدم. حاول كتابة الاسم كاملاً أو جزءاً منه.'];
        }

        if (! $targetUser->isMember()) {
            return ['error' => 'لا يمكن إضافة هذا المستخدم لأنه ليس طالبًا.'];
        }

        $alreadyMember = WorkspaceMembership::where('user_id', $targetUser->id)
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->exists();

        if ($alreadyMember) {
            return ['error' => 'هذا المستخدم عضو في النادي بالفعل.'];
        }

        return [
            'summary' => "إضافة {$targetUser->name} كعضو في نادي {$workspace->name}",
            'changes' => [
                "إنشاء عضوية مقبولة لـ {$targetUser->name} في نادي {$workspace->name}",
            ],
            'params' => [
                'workspace_id' => $workspace->id,
                'user_id' => $targetUser->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $workspace = $this->resolveWorkspace($params['workspace_id']);

        abort_unless($workspace !== null && Gate::allows(WorkspaceCapability::ManageMembers->value, $workspace), 403);

        $targetUser = User::findOrFail($params['user_id']);

        $membership = WorkspaceMembership::updateOrCreate(
            ['user_id' => $targetUser->id, 'workspace_id' => $workspace->id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncWorkspaceRoles([WorkspaceRole::Member]);

        return [
            'success' => true,
            'message' => "تمت إضافة {$targetUser->name} كعضو في نادي {$workspace->name}.",
        ];
    }
}
