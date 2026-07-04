<?php

namespace App\Ai;

use App\Models\Committee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds a small set of starter prompts for the assistant's empty state,
 * tailored to the acting user and the data actually available to them.
 *
 * Suggestions mirror the agent's own capability tiers (guest → member →
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
     * Public prompts for guests. Phase 6 is task-first, so guests are nudged
     * toward login/navigation help instead of private work data.
     *
     * @return Collection<int, string>
     */
    protected function guestSuggestions(): Collection
    {
        return collect([
            __('assistant.suggestions.login_help'),
            __('assistant.suggestions.find_my_tasks_page'),
            __('assistant.suggestions.teamhub_pages'),
            __('assistant.suggestions.teamhub_capabilities'),
        ]);
    }

    /**
     * Personal task prompts, escalating to management prompts when the user
     * oversees a workspace or project.
     *
     * @return Collection<int, string>
     */
    protected function memberSuggestions(User $user): Collection
    {
        $suggestions = [
            __('assistant.suggestions.my_overdue_tasks'),
            __('assistant.suggestions.my_tasks_today'),
            __('assistant.suggestions.my_open_tasks'),
            __('assistant.suggestions.my_upcoming_tasks'),
        ];

        if (($project = $this->exampleProject($user)) !== null) {
            $suggestions[] = __('assistant.suggestions.project_blockers', ['project' => $project->name]);
            $suggestions[] = __('assistant.suggestions.project_summary', ['project' => $project->name]);
        }

        if ($this->managesAnything($user)) {
            $suggestions[] = __('assistant.suggestions.create_task_due_friday');
            $suggestions[] = __('assistant.suggestions.assign_task_example');
            $suggestions[] = __('assistant.suggestions.move_task_to_review');
        }

        return collect($suggestions);
    }

    /**
     * A real project name to anchor example prompts when one is available.
     */
    protected function exampleProject(User $user): ?Committee
    {
        if ($user->isUniversityStaff()) {
            return Committee::query()->orderBy('name')->first();
        }

        return $user->managedCommittees()->first()
            ?? $user->committeeMemberships()
                ->where('status', 'approved')
                ->with('committee')
                ->get()
                ->pluck('committee')
                ->filter()
                ->first();
    }

    /**
     * Whether the user oversees any workspace or project, mirroring the agent's
     * own management-tier gate.
     */
    protected function managesAnything(User $user): bool
    {
        return $user->isUniversityStaff()
            || $user->managedClubs()->isNotEmpty()
            || $user->managedCommittees()->isNotEmpty();
    }
}
