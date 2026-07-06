<?php

namespace App\Ai\Tools;

use App\Models\Project;
use App\Models\ProjectMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Submit a join request for the current user (student) to a committee. The
 * user must already be an approved member of the parent club.
 */
class RequestProjectMembership extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Submit a join request for the current user to a project. '
            .'The user must already be an approved member of the parent workspace.';
    }

    protected function preview(Request $request): array
    {
        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveWorkspace($request['workspace']);
        }

        $project = $this->resolveProject($request['project'] ?? null, $workspace);

        if ($project === null) {
            return ['error' => 'لم يتم العثور على اللجنة.'];
        }

        if (! $this->user->isMember()) {
            return ['error' => 'فقط الطلاب يمكنهم التقدم للانضمام إلى اللجان.'];
        }

        if ($this->user->workspaceMembershipFor($project->workspace) === null) {
            return ['error' => "يجب أن تكون عضوًا في نادي \"{$project->workspace->name}\" للانضمام إلى لجانه."];
        }

        $hasExistingMembership = ProjectMembership::where('project_id', $project->id)
            ->where('user_id', $this->user->id)
            ->exists();

        if ($hasExistingMembership) {
            return ['error' => 'لديك طلب أو عضوية مسبقة في هذه اللجنة.'];
        }

        return [
            'summary' => "تقديم طلب انضمام إلى لجنة \"{$project->name}\"",
            'changes' => [
                "إنشاء طلب انضمام معلّق في لجنة \"{$project->name}\" (نادي {$project->workspace->name})",
            ],
            'params' => ['project_id' => $project->id],
        ];
    }

    public function execute(array $params): array
    {
        $project = Project::with('workspace')->findOrFail($params['project_id']);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "تم إرسال طلب انضمامك إلى لجنة \"{$project->name}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('The project name or numeric ID to join.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional: the parent workspace name or ID to scope the lookup.'),
        ];
    }
}
