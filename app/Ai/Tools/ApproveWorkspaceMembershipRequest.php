<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use App\Notifications\MembershipApprovedNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Approve a pending club join application. Requires ManageMembers capability for the club.
 */
class ApproveWorkspaceMembershipRequest extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Approve a pending workspace membership application. Requires ManageMembers capability for the workspace.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'application_id' => $schema->integer()
                ->description('The numeric ID of the WorkspaceMembershipRequest to approve. Optional if you instead pass applicant + workspace.'),
            'applicant' => $schema->string()
                ->description('The applicant\'s name, used to find their pending application within the workspace. Use this when you do not have a numeric application id.'),
            'workspace' => $schema->string()
                ->description('The workspace name (or numeric id) the application belongs to. Required when resolving by applicant name.'),
        ];
    }

    protected function preview(Request $request): array
    {
        $application = $this->resolvePendingWorkspaceMembershipRequest(
            $request['application_id'] ?? null,
            $request['applicant'] ?? null,
            $request['workspace'] ?? $request['workspace'] ?? null,
        );

        if ($application === null) {
            return ['error' => 'لم يتم العثور على طلب الانضمام.'];
        }

        if ($application->status !== 'pending') {
            return ['error' => 'هذا الطلب ليس في انتظار المراجعة.'];
        }

        if (! Gate::allows(WorkspaceCapability::ManageMembers->value, $application->workspace)) {
            return ['error' => 'ليس لديك صلاحية لمراجعة طلبات هذا النادي.'];
        }

        $applicantName = $application->user?->name ?? $application->full_name;

        return [
            'summary' => "قبول طلب انضمام {$applicantName} في نادي {$application->workspace->name}",
            'changes' => [
                "قبول الطلب وإضافة {$applicantName} كعضو في نادي {$application->workspace->name}",
                'إرسال إشعار بالقبول للمتقدم',
            ],
            'params' => ['application_id' => $application->id],
        ];
    }

    public function execute(array $params): array
    {
        $application = WorkspaceMembershipRequest::with('workspace', 'user')->findOrFail($params['application_id']);

        abort_unless(Gate::allows(WorkspaceCapability::ManageMembers->value, $application->workspace), 403);

        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
        ]);

        $membership = WorkspaceMembership::updateOrCreate(
            ['user_id' => $application->user_id, 'workspace_id' => $application->workspace_id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->assignWorkspaceRole(WorkspaceRole::Member);

        $application->user?->notify(new MembershipApprovedNotification($application->workspace));

        $applicantName = $application->user?->name ?? $application->full_name;

        return [
            'success' => true,
            'message' => "تم قبول انضمام {$applicantName} إلى نادي {$application->workspace->name}.",
        ];
    }
}
