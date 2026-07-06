<?php

namespace App\Ai\Tools;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Add an approved club member directly to a committee. Requires ManageMembers capability for the committee.
 */
class AddProjectMember extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Add an approved workspace member directly to a project. Requires ManageMembers capability for the project.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('Project name or ID.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional: parent workspace name or ID.'),
            'user' => $schema->string()
                ->description('The user name or numeric ID to add.')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $workspace = isset($request['workspace']) ? $this->resolveWorkspace($request['workspace']) : null;

        $project = $this->resolveProject($request['project'] ?? null, $workspace);

        if ($project === null) {
            return ['error' => 'لم يتم العثور على اللجنة.'];
        }

        if (! Gate::allows(ProjectCapability::ManageMembers->value, $project)) {
            return ['error' => 'ليس لديك صلاحية لإدارة أعضاء هذه اللجنة.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على المستخدم. حاول كتابة الاسم كاملاً أو جزءاً منه.'];
        }

        $isWorkspaceMember = WorkspaceMembership::where('user_id', $targetUser->id)
            ->where('workspace_id', $project->workspace_id)
            ->where('status', 'approved')
            ->exists();

        if (! $isWorkspaceMember) {
            return ['error' => 'يجب أن يكون المستخدم عضوًا في مساحة العمل أولاً.'];
        }

        $alreadyMember = ProjectMembership::where('user_id', $targetUser->id)
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->exists();

        if ($alreadyMember) {
            return ['error' => 'المستخدم عضو في اللجنة بالفعل.'];
        }

        return [
            'summary' => "إضافة {$targetUser->name} كعضو في لجنة {$project->name}",
            'changes' => [
                "إنشاء عضوية مقبولة لـ {$targetUser->name} في لجنة {$project->name}",
            ],
            'params' => [
                'project_id' => $project->id,
                'user_id' => $targetUser->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $project = $this->resolveProject($params['project_id']);

        abort_unless($project !== null && Gate::allows(ProjectCapability::ManageMembers->value, $project), 403);

        $targetUser = User::findOrFail($params['user_id']);

        $membership = ProjectMembership::updateOrCreate(
            ['user_id' => $targetUser->id, 'project_id' => $project->id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncProjectRoles([ProjectRole::Member]);

        return [
            'success' => true,
            'message' => "تمت إضافة {$targetUser->name} كعضو في لجنة {$project->name}.",
        ];
    }
}
