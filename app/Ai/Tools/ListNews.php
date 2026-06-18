<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List published news articles, newest first, optionally filtered by keyword.
 * Only posts whose publish date has passed are visible — mirrors the public
 * news feed.
 */
class ListNews extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the latest published news articles, newest first. Optionally filter by a keyword. '
            .'Use this for "what\'s new?" or to find an announcement.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 10), 1), 25);

        $posts = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")))
            ->with('club:id,name')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'club_id', 'title', 'published_at'])
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'club' => $post->club?->name,
                'publishedAt' => $post->published_at?->toIso8601String(),
                'url' => route('news.show', $post),
            ])
            ->all();

        return $this->json(['news' => $posts]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword to filter news by title or body.'),
            'limit' => $schema->integer()->min(1)->max(25)
                ->description('Maximum number of articles to return (default 10).'),
        ];
    }
}
