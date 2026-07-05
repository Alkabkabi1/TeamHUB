<?php

namespace App\Ai\Tools;

use App\Models\ProjectUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Profile of a single active workspace: basic details and latest published updates.
 */
class GetWorkspaceInfo extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get details about a specific club by name or id, including its latest news. '
            .'Use this when the user asks about a particular workspace.';
    }

    public function handle(Request $request): Stringable|string
    {
        $workspace = $this->resolveWorkspace($request['workspace'] ?? null, activeOnly: true);

        if ($workspace === null) {
            return $this->json(['error' => 'No active workspace matched that name.']);
        }

        return $this->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'membersCount' => $workspace->memberships()->where('status', 'approved')->count(),
                'url' => route('workspaces.show', $workspace),
            ],
            'latestNews' => ProjectUpdate::query()
                ->where('workspace_id', $workspace->id)
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
            'workspace' => $schema->string()
                ->description('The workspace name (or numeric id) to look up.')
                ->required(),
        ];
    }
}
