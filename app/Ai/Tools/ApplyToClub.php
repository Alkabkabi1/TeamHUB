<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\User;
use App\Notifications\JoinApplicationReceivedNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Submit a join application for the current user (student) to a club, using
 * the two-phase confirm flow. Prevents duplicate pending or approved records.
 */
class ApplyToClub extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Submit a join application for the current user (student) to a club.';
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club'] ?? null, activeOnly: true);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! $this->user->isStudent()) {
            return ['error' => 'فقط الطلاب يمكنهم التقدم للانضمام إلى الأندية.'];
        }

        $hasPendingApplication = ClubJoinApplication::where('user_id', $this->user->id)
            ->where('club_id', $club->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            return ['error' => 'لديك طلب انضمام معلّق لهذا النادي.'];
        }

        $isApprovedMember = ClubMembership::where('user_id', $this->user->id)
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->exists();

        if ($isApprovedMember) {
            return ['error' => 'أنت عضو في هذا النادي بالفعل.'];
        }

        return [
            'summary' => "تقديم طلب انضمام إلى نادي \"{$club->name}\"",
            'changes' => ["إنشاء طلب انضمام معلّق في نادي \"{$club->name}\""],
            'params' => ['club_id' => $club->id],
        ];
    }

    public function execute(array $params): array
    {
        $club = Club::findOrFail($params['club_id']);

        $application = ClubJoinApplication::create([
            'full_name' => $this->user->name,
            'university_email' => $this->user->email,
            'club_id' => $club->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $reviewerIds = ClubMembership::query()
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'role',
                ClubRole::valuesWithCapability(ClubCapability::ManageMembers),
            ))
            ->pluck('user_id')
            ->unique();

        $reviewers = User::whereIn('id', $reviewerIds)->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new JoinApplicationReceivedNotification($application));
        }

        return [
            'success' => true,
            'message' => "تم إرسال طلب انضمامك إلى نادي \"{$club->name}\" بنجاح. سيتم مراجعته قريبًا.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name or numeric ID to apply to.')
                ->required(),
        ];
    }
}
