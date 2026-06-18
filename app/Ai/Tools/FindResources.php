<?php

namespace App\Ai\Tools;

use App\Models\ClubResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List or search club resources (downloadable files and media), optionally by
 * keyword, parent club, or type.
 */
class FindResources extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List or search club resources — downloadable files and media — by keyword, club, or type '
            .'("download" or "media"). Use this when the user asks about materials, files, or media.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $clubName = trim((string) ($request['club'] ?? ''));
        $type = trim((string) ($request['type'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 15), 1), 30);

        $resources = ClubResource::query()
            ->with('club:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($clubName !== '', fn ($q) => $q->whereHas('club', fn ($c) => $c->where('name', 'like', "%{$clubName}%")))
            ->when(in_array($type, [ClubResource::TYPE_DOWNLOAD, ClubResource::TYPE_MEDIA], true), fn ($q) => $q->where('type', $type))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'club_id', 'type', 'title', 'description', 'format'])
            ->map(fn (ClubResource $resource): array => [
                'title' => $resource->title,
                'description' => $resource->description,
                'type' => $resource->type,
                'format' => $resource->format,
                'club' => $resource->club?->name,
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
            'club' => $schema->string()
                ->description('Optional club name to list only that club\'s resources.'),
            'type' => $schema->string()
                ->enum([ClubResource::TYPE_DOWNLOAD, ClubResource::TYPE_MEDIA])
                ->description('Optional filter: "download" for files, "media" for media items.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of resources to return (default 15).'),
        ];
    }
}
