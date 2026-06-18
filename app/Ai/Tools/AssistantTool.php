<?php

namespace App\Ai\Tools;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\Committee;
use App\Models\Event;
use App\Models\User;
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
}
