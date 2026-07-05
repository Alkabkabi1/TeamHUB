<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

class ProjectPresenter
{
    /** @var list<string> */
    private const COLORS = ['#7c3aed', '#16a34a', '#2563eb', '#c8924a', '#dc2626', '#0891b2'];

    /** @var list<string> */
    private const ICONS = ['monitor', 'mobile', 'web', 'megaphone'];

    /**
     * @return array<string, mixed>
     */
    public function card(Project $project): array
    {
        $total = (int) $project->tasks_count;
        $done = (int) ($project->done_tasks_count ?? 0);
        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;
        $color = self::COLORS[$project->id % count(self::COLORS)];
        $icon = self::ICONS[$project->id % count(self::ICONS)];

        $members = $project->memberships
            ->take(4)
            ->map(fn ($membership) => $this->initials((string) $membership->user?->name))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'title' => $project->name,
            'description' => $project->description ?? '',
            'progress' => $progress,
            'tasksCount' => $total,
            'membersCount' => (int) ($project->members_count ?? 0),
            'color' => $project->theme ?: ($project->workspace?->theme ?: $color),
            'icon' => $icon,
            'members' => $members,
            'url' => route('projects.tasks.index', [$project->workspace_id, $project], absolute: false),
            'manage_url' => route('projects.manage', [$project->workspace_id, $project], absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leaderSummary(Project $project, int $openTasks): array
    {
        $total = (int) ($project->tasks_count ?? 0);
        $done = (int) ($project->done_tasks_count ?? 0);
        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        return [
            'id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'title' => $project->name,
            'workspace' => $project->workspace?->name ?? '',
            'progress' => $progress,
            'tasks_count' => $total,
            'open_tasks' => $openTasks,
            'url' => route('projects.tasks.index', [$project->workspace_id, $project], absolute: false),
            'manage_url' => route('projects.manage', [$project->workspace_id, $project], absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminListItem(Project $project, ?User $leader, int $progress, int $tasksCount): array
    {
        return [
            'id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'title' => $project->name,
            'workspace' => $project->workspace?->name ?? '',
            'progress' => $progress,
            'tasks_count' => $tasksCount,
            'leader' => $leader ? [
                'id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
            ] : null,
            'url' => route('projects.tasks.index', [$project->workspace_id, $project], absolute: false),
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1));
    }
}
