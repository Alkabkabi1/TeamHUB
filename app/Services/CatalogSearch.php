<?php

namespace App\Services;

use App\Concerns\FiltersCatalog;
use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared, publicly-visible catalog search across clubs, events, news, and
 * resources. Backs both the global command-palette endpoint (SearchController)
 * and the assistant's SearchCatalog tool, ensuring both surface the exact same
 * records.
 */
class CatalogSearch
{
    use FiltersCatalog;

    /**
     * Number of results returned per entity group.
     */
    public const PER_GROUP = 5;

    /**
     * Minimum query length before any lookup runs.
     */
    public const MIN_LENGTH = 2;

    /**
     * Search every catalog group for the given term. Terms shorter than the
     * minimum length yield empty groups.
     *
     * @return array{clubs: list<array{id: int, title: string, subtitle: string, url: string}>, events: list<array{id: int, title: string, subtitle: string, url: string}>, news: list<array{id: int, title: string, subtitle: string, url: string}>, resources: list<array{id: int, title: string, subtitle: string, url: string}>}
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return $this->emptyGroups();
        }

        return [
            'clubs' => $this->clubs($term),
            'events' => $this->events($term),
            'news' => $this->news($term),
            'resources' => $this->resources($term),
        ];
    }

    /**
     * @return list<array{id: int, title: string, subtitle: string, url: string}>
     */
    private function clubs(string $term): array
    {
        return Club::query()
            ->where('status', 'active')
            ->tap(fn (Builder $query) => $this->applySearch($query, $term, ['name', 'category', 'college']))
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get(['id', 'name', 'category', 'college'])
            ->map(fn (Club $club): array => [
                'id' => $club->id,
                'title' => $club->name,
                'subtitle' => $club->category ?? $club->college ?? '',
                'url' => route('clubs.show', $club),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, title: string, subtitle: string, url: string}>
     */
    private function events(string $term): array
    {
        return Event::query()
            ->active()
            ->with('club:id,name')
            ->tap(fn (Builder $query) => $this->applySearch($query, $term, ['title', 'description', 'location']))
            ->orderBy('starts_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'club_id', 'title', 'location', 'starts_at'])
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'subtitle' => $event->club?->name ?? ($event->location ?? ''),
                'url' => route('events.show', $event),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, title: string, subtitle: string, url: string}>
     */
    private function news(string $term): array
    {
        return Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('club:id,name')
            ->tap(fn (Builder $query) => $this->applySearch($query, $term, ['title', 'body']))
            ->orderByDesc('published_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'club_id', 'title', 'published_at'])
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'subtitle' => $post->club?->name ?? '',
                'url' => route('news.show', $post),
            ])
            ->all();
    }

    /**
     * Resources have no detail route, so each result deep-links into the
     * resources catalog pre-filtered by its title.
     *
     * @return list<array{id: int, title: string, subtitle: string, url: string}>
     */
    private function resources(string $term): array
    {
        return ClubResource::query()
            ->with('club:id,name')
            ->tap(fn (Builder $query) => $this->applySearch($query, $term, ['title', 'description']))
            ->orderByDesc('published_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'club_id', 'title'])
            ->map(fn (ClubResource $resource): array => [
                'id' => $resource->id,
                'title' => $resource->title,
                'subtitle' => $resource->club?->name ?? '',
                'url' => route('resources', ['search' => $resource->title]),
            ])
            ->all();
    }

    /**
     * @return array{clubs: array<empty>, events: array<empty>, news: array<empty>, resources: array<empty>}
     */
    private function emptyGroups(): array
    {
        return ['clubs' => [], 'events' => [], 'news' => [], 'resources' => []];
    }
}
