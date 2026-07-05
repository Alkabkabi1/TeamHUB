<?php

namespace App\Ai\Tools;

use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List or search active clubs — optionally by keyword, college, or category.
 * Use this to browse all clubs when no exact name is known.
 */
class FindWorkspaces extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List or browse active clubs, optionally filtered by keyword, college, or category. '
            .'Use this for "what clubs are available?" or "clubs in the engineering college".';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $college = trim((string) ($request['college'] ?? ''));
        $category = trim((string) ($request['category'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 20), 1), 50);

        $workspaces = Workspace::query()
            ->where('status', 'active')
            ->withCount('memberships as members_count')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('college', 'like', "%{$search}%")))
            ->when($college !== '', fn ($q) => $q->where('college', 'like', "%{$college}%"))
            ->when($category !== '', fn ($q) => $q->where('category', 'like', "%{$category}%"))
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'category', 'college'])
            ->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'category' => $workspace->category,
                'college' => $workspace->college,
                'membersCount' => (int) $workspace->members_count,
                'url' => route('workspaces.show', $workspace),
            ])
            ->all();

        return $this->json(['workspaces' => $workspaces]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword matched against club name, category, or college.'),
            'college' => $schema->string()
                ->description('Optional college name to filter by.'),
            'category' => $schema->string()
                ->description('Optional category to filter by (e.g. تقني، ثقافي، تطوعي).'),
            'limit' => $schema->integer()->min(1)->max(50)
                ->description('Maximum number of clubs to return (default 20).'),
        ];
    }
}
