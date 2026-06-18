<?php

namespace App\Ai\Tools;

use App\Models\Event;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Profile of a single committee (sub-club): basic details plus its upcoming
 * events and most recent published news.
 */
class GetCommitteeInfo extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get details about a specific committee (a sub-group within a club) by name, optionally within '
            .'a named club, including its upcoming events and latest news.';
    }

    public function handle(Request $request): Stringable|string
    {
        $club = $this->resolveClub($request['club'] ?? null, activeOnly: true);
        $committee = $this->resolveCommittee($request['committee'] ?? null, $club);

        if ($committee === null) {
            return $this->json(['error' => 'No committee matched that name.']);
        }

        return $this->json([
            'committee' => [
                'id' => $committee->id,
                'name' => $committee->name,
                'club' => $committee->club?->name,
                'description' => $committee->description,
                'membersCount' => $committee->memberships()->where('status', 'approved')->count(),
            ],
            'upcomingEvents' => Event::query()
                ->where('committee_id', $committee->id)
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
                ->where('committee_id', $committee->id)
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
            'committee' => $schema->string()
                ->description('The committee name (or numeric id) to look up.')
                ->required(),
            'club' => $schema->string()
                ->description('Optional parent club name to disambiguate the committee.'),
        ];
    }
}
