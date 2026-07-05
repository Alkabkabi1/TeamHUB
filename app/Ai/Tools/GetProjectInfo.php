<?php

namespace App\Ai\Tools;

use App\Models\ProjectUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Profile of a single project inside a workspace.
 */
class GetProjectInfo extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get details about a specific committee (a sub-group within a club) by name, optionally within '
            .'a named club, including its latest news.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? null, activeOnly: true);
        $project = $this->resolveProject($request['project'] ?? null, $workspace);

        if ($project === null) {
            return $this->json(['error' => 'No committee matched that name.']);
        }

        $project->loadMissing('workspace:id,name');

        return $this->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'workspace' => $project->workspace?->name,
                'description' => $project->description,
                'membersCount' => $project->memberships()->where('status', 'approved')->count(),
            ],
            'latestNews' => ProjectUpdate::query()
                ->where('project_id', $project->id)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'published_at'])
                ->map(fn (ProjectUpdate $post): array => [
                    'title' => $post->title,
                    'publishedAt' => $post->published_at?->toIso8601String(),
                ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('The project name (or numeric id) to look up.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional parent club name to disambiguate the committee.'),
        ];
    }
}
