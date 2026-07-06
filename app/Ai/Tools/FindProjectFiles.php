<?php

namespace App\Ai\Tools;

use App\Models\ProjectFile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List or search workspace resources (downloadable files and media).
 */
class FindProjectFiles extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List or search project files — downloadable files and media — by keyword, workspace, or type '
            .'("download" or "media"). Use this when the user asks about materials, files, or media.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $workspaceName = trim((string) ($request['workspace'] ?? ''));
        $type = trim((string) ($request['type'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 15), 1), 30);

        $resources = ProjectFile::query()
            ->with('workspace:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($workspaceName !== '', fn ($q) => $q->whereHas('workspace', fn ($c) => $c->where('name', 'like', "%{$workspaceName}%")))
            ->when(in_array($type, [ProjectFile::TYPE_DOWNLOAD, ProjectFile::TYPE_MEDIA], true), fn ($q) => $q->where('type', $type))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'workspace_id', 'type', 'title', 'description', 'format'])
            ->map(fn (ProjectFile $resource): array => [
                'title' => $resource->title,
                'description' => $resource->description,
                'type' => $resource->type,
                'format' => $resource->format,
                'workspace' => $resource->workspace?->name,
                'url' => route('resources', ['search' => $resource->title]),
            ])
            ->all();

        return $this->json(['resources' => $resources]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword matched against resource title or description.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to list only that workspace\'s resources.'),
            'type' => $schema->string()
                ->enum([ProjectFile::TYPE_DOWNLOAD, ProjectFile::TYPE_MEDIA])
                ->description('Optional filter: "download" for files, "media" for media items.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of resources to return (default 15).'),
        ];
    }
}
