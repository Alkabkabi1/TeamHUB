<?php

namespace App\Ai\Tools;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;

/**
 * Base class for every assistant tool. Each tool is constructed with the
 * authenticated user and runs queries scoped to — and authorized against —
 * that user, so the assistant can never surface data the user could not see
 * themselves.
 */
abstract class AssistantTool implements Tool
{
    /**
     * The acting user, or null for an unauthenticated guest. Guests are only
     * ever given the public-data tools, which do not read this property.
     */
    public function __construct(protected ?User $user = null) {}

    /**
     * Encode a structured result as a compact JSON string for the model.
     *
     * @param  array<string, mixed>  $data
     */
    protected function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Resolve a club from a free-text identifier (numeric id or name). Pass
     * $activeOnly for public lookups; capability-gated tools resolve any
     * non-archived club and rely on the Gate to authorize access.
     */
    protected function resolveClub(int|string|null $identifier, bool $activeOnly = false): ?Club
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Club::query()->when($activeOnly, fn ($q) => $q->where('status', 'active'));

        if (ctype_digit($identifier)) {
            return $query->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    /**
     * Resolve a user from a free-text identifier (numeric id or full/partial
     * name). When $club is given the search is scoped to approved members of
     * that club, which narrows ambiguous name matches to the relevant pool.
     */
    protected function resolveUser(int|string|null $identifier, ?Club $club = null): ?User
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = User::query()
            ->when(
                $club !== null,
                fn ($q) => $q->whereIn(
                    'id',
                    $club->memberships()->where('status', 'approved')->select('user_id'),
                ),
            );

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    /**
     * Resolve an event from a free-text identifier (numeric id or title
     * substring), optionally constrained to a parent club.
     */
    protected function resolveEvent(int|string|null $identifier, ?Club $club = null): ?Event
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Event::query()
            ->when($club !== null, fn ($q) => $q->where('club_id', $club->id));

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('title', 'like', "%{$identifier}%")->orderByDesc('starts_at')->first();
        }

        return $query->where('title', 'like', "%{$identifier}%")->orderByDesc('starts_at')->first();
    }

    /**
     * Resolve a pending club join application, either by its numeric id or by
     * matching an applicant's name within a given club. The by-name path lets
     * the assistant act on an applicant the user just named (e.g. after listing
     * pending applications) without having to surface a raw id. Returns null
     * when nothing unambiguously matches.
     */
    protected function resolvePendingClubApplication(
        int|string|null $applicationId,
        int|string|null $applicant = null,
        int|string|null $club = null,
    ): ?ClubJoinApplication {
        $applicationId = trim((string) $applicationId);

        if ($applicationId !== '' && ctype_digit($applicationId)) {
            return ClubJoinApplication::query()
                ->with('club', 'user')
                ->whereKey((int) $applicationId)
                ->first();
        }

        $clubModel = $this->resolveClub($club);
        $applicant = trim((string) $applicant);

        if ($clubModel === null || $applicant === '') {
            return null;
        }

        return ClubJoinApplication::query()
            ->with('club', 'user')
            ->where('club_id', $clubModel->id)
            ->where('status', 'pending')
            ->where(function ($query) use ($applicant): void {
                $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$applicant}%"))
                    ->orWhere('full_name', 'like', "%{$applicant}%");
            })
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Resolve a committee from a free-text identifier (numeric id or name),
     * optionally constrained to a parent club.
     */
    protected function resolveCommittee(int|string|null $identifier, ?Club $club = null): ?Committee
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Committee::query()
            ->when($club !== null, fn ($q) => $q->where('club_id', $club->id))
            ->with('club');

        if (ctype_digit($identifier)) {
            return $query->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    /**
     * Resolve a task from a free-text identifier (numeric id or title),
     * optionally constrained to a single project/committee and always scoped to
     * the current user's visible projects.
     */
    protected function resolveTask(int|string|null $identifier, ?Committee $committee = null): ?Task
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '' || $this->user === null) {
            return null;
        }

        $query = $this->visibleTaskQuery()
            ->when($committee !== null, fn (Builder $q) => $q->where('committee_id', $committee->id));

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('title', 'like', "%{$identifier}%")->orderByDesc('updated_at')->first();
        }

        return $query->where('title', 'like', "%{$identifier}%")->orderByDesc('updated_at')->first();
    }

    /**
     * Resolve an approved project member from a free-text identifier (numeric id
     * or full/partial name) inside one committee only.
     */
    protected function resolveCommitteeMember(int|string|null $identifier, Committee $committee): ?User
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = User::query()->whereIn(
            'id',
            $committee->memberships()->where('status', 'approved')->select('user_id'),
        );

        if (ctype_digit($identifier)) {
            return $query->clone()->whereKey((int) $identifier)->first()
                ?? $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
        }

        return $query->where('name', 'like', "%{$identifier}%")->orderBy('name')->first();
    }

    /**
     * Whether the acting user may view a project's tasks.
     */
    protected function canAccessCommittee(Committee $committee): bool
    {
        if ($this->user === null) {
            return false;
        }

        if ($this->user->isUniversityStaff() || $this->user->canManageCommittee($committee)) {
            return true;
        }

        return $this->user->committeeMemberships()
            ->where('committee_id', $committee->id)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Resolve a project visible to the acting user.
     */
    protected function resolveAccessibleCommittee(
        int|string|null $identifier,
        ?Club $club = null,
    ): ?Committee {
        $committee = $this->resolveCommittee($identifier, $club);

        if ($committee === null || ! $this->canAccessCommittee($committee)) {
            return null;
        }

        return $committee;
    }

    /**
     * A base query for tasks the acting user may view.
     *
     * @return Builder<Task>
     */
    protected function visibleTaskQuery(): Builder
    {
        /** @var User $user */
        $user = $this->user;

        $query = Task::query()
            ->with([
                'committee:id,club_id,name',
                'committee.club:id,name',
                'assignee:id,name',
                'creator:id,name',
            ]);

        if ($user->isUniversityStaff()) {
            return $query;
        }

        return $query->whereIn('committee_id', $this->accessibleCommitteeIds());
    }

    /**
     * @return array<int, int>
     */
    protected function accessibleCommitteeIds(): array
    {
        if ($this->user === null) {
            return [];
        }

        if ($this->user->isUniversityStaff()) {
            return Committee::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        }

        $membershipIds = $this->user->committeeMemberships()
            ->where('status', 'approved')
            ->pluck('committee_id');

        $managedCommitteeIds = $this->user->managedCommittees()->pluck('id');
        $managedClubIds = $this->user->managedClubs()->pluck('id');

        $managedClubCommitteeIds = $managedClubIds->isEmpty()
            ? collect()
            : Committee::query()
                ->whereIn('club_id', $managedClubIds)
                ->pluck('id');

        return $membershipIds
            ->merge($managedCommitteeIds)
            ->merge($managedClubCommitteeIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priority_label' => __($task->priority->label()),
            'due_at' => $task->due_at?->toIso8601String(),
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'workspace' => [
                'id' => $task->committee?->club_id,
                'name' => $task->committee?->club?->name ?? '',
                'manage_url' => $task->committee?->club_id
                    ? route('clubs.manage', [$task->committee->club_id], absolute: false)
                    : null,
            ],
            'project' => [
                'id' => $task->committee_id,
                'name' => $task->committee?->name ?? '',
                'tasks_url' => $task->committee?->club_id
                    ? route('committees.tasks.index', [$task->committee->club_id, $task->committee_id], absolute: false)
                    : null,
                'manage_url' => $task->committee?->club_id
                    ? route('committees.manage', [$task->committee->club_id, $task->committee_id], absolute: false)
                    : null,
            ],
            'detail_url' => $task->committee?->club_id
                ? route('committees.tasks.show', [$task->committee->club_id, $task->committee_id, $task], absolute: false)
                : null,
            'has_deliverable' => $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null
                || filled($task->deliverable_url)
                || filled($task->deliverable_notes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentActivity(TaskActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'message' => $activity->message(),
            'created_at' => $activity->created_at?->toIso8601String(),
            'actor_name' => $activity->user?->name ?? __('tasks.activity.system'),
            'task_title' => $activity->task?->title ?? '',
        ];
    }
}
