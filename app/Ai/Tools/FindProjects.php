<?php

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindProjects extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List or search projects by keyword and/or parent workspace.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $workspaceName = trim((string) ($request['workspace'] ?? $request['workspace'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 15), 1), 30);

        $projects = Project::query()
            ->with('workspace:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($workspaceName !== '', fn ($q) => $q->whereHas('workspace', fn ($c) => $c->where('name', 'like', "%{$workspaceName}%")))
            ->orderBy('workspace_id')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'workspace' => $project->workspace?->name,
                'description' => $project->description,
                'status' => $project->status?->value,
                'membersCount' => $project->memberships()->where('status', 'approved')->count(),
                'url' => route('projects.show', ['workspace' => $project->workspace_id, 'project' => $project->id]),
            ])
            ->all();

        return $this->json(['projects' => $projects]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword to match against project name or description.'),
            'workspace' => $schema->string()
                ->description('Optional parent workspace name to list only that workspace\'s projects.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of projects to return (default 15).'),
        ];
    }
}
