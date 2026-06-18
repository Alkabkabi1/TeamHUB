<?php

namespace App\Ai\Tools;

use App\Models\Committee;
use App\Models\CommitteeMembership;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Submit a join request for the current user (student) to a committee. The
 * user must already be an approved member of the parent club.
 */
class ApplyToCommittee extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Submit a join request for the current user (student) to a committee. '
            .'The user must already be an approved member of the parent club.';
    }

    protected function preview(Request $request): array
    {
        $club = null;

        if (! empty($request['club'])) {
            $club = $this->resolveClub($request['club']);
        }

        $committee = $this->resolveCommittee($request['committee'] ?? null, $club);

        if ($committee === null) {
            return ['error' => 'لم يتم العثور على اللجنة.'];
        }

        if (! $this->user->isStudent()) {
            return ['error' => 'فقط الطلاب يمكنهم التقدم للانضمام إلى اللجان.'];
        }

        if ($this->user->clubMembershipFor($committee->club) === null) {
            return ['error' => "يجب أن تكون عضوًا في نادي \"{$committee->club->name}\" للانضمام إلى لجانه."];
        }

        $hasExistingMembership = CommitteeMembership::where('committee_id', $committee->id)
            ->where('user_id', $this->user->id)
            ->exists();

        if ($hasExistingMembership) {
            return ['error' => 'لديك طلب أو عضوية مسبقة في هذه اللجنة.'];
        }

        return [
            'summary' => "تقديم طلب انضمام إلى لجنة \"{$committee->name}\"",
            'changes' => [
                "إنشاء طلب انضمام معلّق في لجنة \"{$committee->name}\" (نادي {$committee->club->name})",
            ],
            'params' => ['committee_id' => $committee->id],
        ];
    }

    public function execute(array $params): array
    {
        $committee = Committee::with('club')->findOrFail($params['committee_id']);

        CommitteeMembership::create([
            'committee_id' => $committee->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "تم إرسال طلب انضمامك إلى لجنة \"{$committee->name}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'committee' => $schema->string()
                ->description('The committee name or numeric ID to join.')
                ->required(),
            'club' => $schema->string()
                ->description('Optional: the parent club name or ID to scope the lookup.'),
        ];
    }
}
