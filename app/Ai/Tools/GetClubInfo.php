<?php

namespace App\Ai\Tools;

use App\Models\Club;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Profile of a single active club: basic details plus its upcoming events and
 * most recent published news.
 */
class GetClubInfo extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get details about a specific club by name or id, including its upcoming events and latest news. '
            .'Use this when the user asks about a particular club.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null, activeOnly: true);

        if ($club === null) {
            return $this->json(['error' => 'No active club matched that name.']);
        }

        return $this->json([
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'category' => $club->category,
                'college' => $club->college,
                'membersCount' => $club->memberships()->where('status', 'approved')->count(),
                'url' => route('clubs.show', $club),
            ],
            'upcomingEvents' => Event::query()
                ->where('club_id', $club->id)
                ->active()->upcoming()
                ->orderBy('starts_at')
                ->limit(5)
                ->get(['id', 'title', 'starts_at', 'location'])
                ->map(fn (Event $event): array => [
                    'title' => $event->title,
                    'startsAt' => $event->starts_at?->toIso8601String(),
                    'location' => $event->location,
                ])->all(),
            'latestNews' => Post::query()
                ->where('club_id', $club->id)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'published_at'])
                ->map(fn (Post $post): array => [
                    'title' => $post->title,
                    'publishedAt' => $post->published_at?->toIso8601String(),
                ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('The club name (or numeric id) to look up.')
                ->required(),
        ];
    }
}
