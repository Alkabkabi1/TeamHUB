<?php

namespace App\Services;

use App\Concerns\FiltersCatalog;
use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

class CatalogSearch
{
    use FiltersCatalog;

    public const PER_GROUP = 5;

    public const MIN_LENGTH = 2;

    /**
     * @return array{clubs: list<array{id: int, title: string, subtitle: string, url: string}>, updates: list<array{id: int, title: string, subtitle: string, url: string}>, resources: list<array{id: int, title: string, subtitle: string, url: string}>}
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return $this->emptyGroups();
        }

        return [
            'clubs' => $this->clubs($term),
            'updates' => $this->updates($term),
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
    private function updates(string $term): array
    {
        return Post::query()
            ->whereNotNull('committee_id')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['club:id,name', 'committee:id,name,club_id'])
            ->tap(fn (Builder $query) => $this->applySearch($query, $term, ['title', 'body']))
            ->orderByDesc('published_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'club_id', 'committee_id', 'title', 'published_at'])
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'subtitle' => trim(($post->committee?->name ?? '').' · '.($post->club?->name ?? '')),
                'url' => route('committees.updates.index', [$post->club_id, $post->committee_id]),
            ])
            ->all();
    }

    /**
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
     * @return array{clubs: array<empty>, updates: array<empty>, resources: array<empty>}
     */
    private function emptyGroups(): array
    {
        return ['clubs' => [], 'updates' => [], 'resources' => []];
    }
}
