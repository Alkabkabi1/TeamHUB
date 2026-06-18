<?php

namespace App\Ai;

use App\Models\Club;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds a small set of starter prompts for the assistant's empty state,
 * tailored to the acting user and the data actually available to them.
 *
 * Suggestions mirror the agent's own capability tiers (guest → student →
 * manager) and deliberately mix read-only lookups with write actions. Each
 * string is a natural-language prompt in the active locale: clicking a pill
 * sends it verbatim, and the assistant replies in that same language.
 */
class AssistantSuggestionService
{
    /**
     * Up to {@see self::LIMIT} shuffled suggestions for the given user (or a
     * guest when null).
     *
     * @return list<string>
     */
    public function for(?User $user, int $limit = 5): array
    {
        $pool = $user === null
            ? $this->guestSuggestions()
            : $this->memberSuggestions($user);

        return $pool->shuffle()->take($limit)->values()->all();
    }

    /**
     * Public, read-only prompts anyone can ask without signing in.
     *
     * @return Collection<int, string>
     */
    protected function guestSuggestions(): Collection
    {
        $suggestions = [
            __('assistant.suggestions.upcoming_events'),
            __('assistant.suggestions.browse_clubs'),
            __('assistant.suggestions.latest_news'),
            __('assistant.suggestions.find_resources'),
            __('assistant.suggestions.find_committees'),
            __('assistant.suggestions.how_to_join'),
        ];

        if (($event = $this->nextOpenEvent()) !== null) {
            $suggestions[] = __('assistant.suggestions.event_details', ['event' => $event->title]);
        }

        return collect($suggestions);
    }

    /**
     * Personal read prompts plus member write actions, escalating to
     * management prompts when the user oversees a club or committee.
     *
     * @return Collection<int, string>
     */
    protected function memberSuggestions(User $user): Collection
    {
        // Personal, read-only.
        $suggestions = [
            __('assistant.suggestions.my_registrations'),
            __('assistant.suggestions.my_certificates'),
            __('assistant.suggestions.my_volunteer_hours'),
            __('assistant.suggestions.my_clubs'),
            __('assistant.suggestions.upcoming_events'),
        ];

        // Member write action — register for a concrete open event when one exists.
        if (($event = $this->nextOpenEvent()) !== null) {
            $suggestions[] = __('assistant.suggestions.register_event', ['event' => $event->title]);
        } else {
            $suggestions[] = __('assistant.suggestions.browse_clubs');
        }

        if ($this->managesAnything($user)) {
            $club = $user->isUniversityStaff()
                ? Club::query()->where('status', 'active')->orderBy('name')->first()
                : $user->managedClubs()->first();

            $clubName = $club?->name;

            // Management read + write, anchored to a real managed club when known.
            $suggestions[] = $clubName !== null
                ? __('assistant.suggestions.club_report', ['club' => $clubName])
                : __('assistant.suggestions.pending_applications');
            $suggestions[] = __('assistant.suggestions.pending_applications');
            $suggestions[] = $clubName !== null
                ? __('assistant.suggestions.create_event', ['club' => $clubName])
                : __('assistant.suggestions.create_news');
        }

        return collect($suggestions);
    }

    /**
     * The soonest upcoming event still open for registration, if any. Kept
     * cheap with a small window that is then filtered in memory.
     */
    protected function nextOpenEvent(): ?Event
    {
        return Event::query()
            ->active()
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->first(fn (Event $event): bool => $event->isOpenForRegistration());
    }

    /**
     * Whether the user oversees any club or committee, mirroring the agent's
     * own management-tier gate.
     */
    protected function managesAnything(User $user): bool
    {
        return $user->isUniversityStaff()
            || $user->managedClubs()->isNotEmpty()
            || $user->managedCommittees()->isNotEmpty();
    }
}
