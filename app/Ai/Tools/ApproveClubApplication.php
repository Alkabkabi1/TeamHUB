<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Notifications\MembershipApprovedNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Approve a pending club join application. Requires ManageMembers capability for the club.
 */
class ApproveClubApplication extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Approve a pending club join application. Requires ManageMembers capability for the club.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'application_id' => $schema->integer()
                ->description('The numeric ID of the ClubJoinApplication to approve. Optional if you instead pass applicant + club.'),
            'applicant' => $schema->string()
                ->description('The applicant\'s name, used to find their pending application within "club". Use this when you do not have a numeric application id.'),
            'club' => $schema->string()
                ->description('The club name (or numeric id) the application belongs to. Required when resolving by applicant name.'),
        ];
    }

    protected function preview(Request $request): array
    {
        $application = $this->resolvePendingClubApplication(
            $request['application_id'] ?? null,
            $request['applicant'] ?? null,
            $request['club'] ?? null,
        );

        if ($application === null) {
            return ['error' => 'لم يتم العثور على طلب الانضمام.'];
        }

        if ($application->status !== 'pending') {
            return ['error' => 'هذا الطلب ليس في انتظار المراجعة.'];
        }

        if (! Gate::allows(ClubCapability::ManageMembers->value, $application->club)) {
            return ['error' => 'ليس لديك صلاحية لمراجعة طلبات هذا النادي.'];
        }

        $applicantName = $application->user?->name ?? $application->full_name;

        return [
            'summary' => "قبول طلب انضمام {$applicantName} في نادي {$application->club->name}",
            'changes' => [
                "قبول الطلب وإضافة {$applicantName} كعضو في نادي {$application->club->name}",
                'إرسال إشعار بالقبول للمتقدم',
            ],
            'params' => ['application_id' => $application->id],
        ];
    }

    public function execute(array $params): array
    {
        $application = ClubJoinApplication::with('club', 'user')->findOrFail($params['application_id']);

        abort_unless(Gate::allows(ClubCapability::ManageMembers->value, $application->club), 403);

        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
        ]);

        $membership = ClubMembership::updateOrCreate(
            ['user_id' => $application->user_id, 'club_id' => $application->club_id],
            [
                'status' => 'approved',
                'reviewed_by' => $this->user->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->assignClubRole(ClubRole::Member);

        $application->user?->notify(new MembershipApprovedNotification($application->club));

        $applicantName = $application->user?->name ?? $application->full_name;

        return [
            'success' => true,
            'message' => "تم قبول انضمام {$applicantName} إلى نادي {$application->club->name}.",
        ];
    }
}
