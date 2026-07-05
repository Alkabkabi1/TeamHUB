<?php

namespace App\Ai\Tools;

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use App\Notifications\JoinApplicationReceivedNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Submit a join application for the current user (student) to a club, using
 * the two-phase confirm flow. Prevents duplicate pending or approved records.
 */
class RequestWorkspaceMembership extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Submit a join application for the current user (student) to a club.';
    }

    protected function preview(Request $request): array
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? null, activeOnly: true);

        if ($workspace === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! $this->user->isMember()) {
            return ['error' => 'فقط الطلاب يمكنهم التقدم للانضمام إلى الأندية.'];
        }

        $hasPendingApplication = WorkspaceMembershipRequest::where('user_id', $this->user->id)
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            return ['error' => 'لديك طلب انضمام معلّق لهذا النادي.'];
        }

        $isApprovedMember = WorkspaceMembership::where('user_id', $this->user->id)
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->exists();

        if ($isApprovedMember) {
            return ['error' => 'أنت عضو في هذا النادي بالفعل.'];
        }

        return [
            'summary' => "تقديم طلب انضمام إلى نادي \"{$workspace->name}\"",
            'changes' => ["إنشاء طلب انضمام معلّق في نادي \"{$workspace->name}\""],
            'params' => ['workspace_id' => $workspace->id],
        ];
    }

    public function execute(array $params): array
    {
        $workspace = Workspace::findOrFail($params['workspace_id']);

        $application = WorkspaceMembershipRequest::create([
            'full_name' => $this->user->name,
            'university_email' => $this->user->email,
            'workspace_id' => $workspace->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $reviewerIds = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'role',
                WorkspaceRole::valuesWithCapability(WorkspaceCapability::ManageMembers),
            ))
            ->pluck('user_id')
            ->unique();

        $reviewers = User::whereIn('id', $reviewerIds)->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new JoinApplicationReceivedNotification($application));
        }

        return [
            'success' => true,
            'message' => "تم إرسال طلب انضمامك إلى نادي \"{$workspace->name}\" بنجاح. سيتم مراجعته قريبًا.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->string()
                ->description('The workspace name or numeric ID to apply to.')
                ->required(),
        ];
    }
}
