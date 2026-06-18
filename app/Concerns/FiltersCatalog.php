<?php

namespace App\Concerns;

use App\Models\Tag;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Shared search + tag + sort filtering for the public catalog pages
 * (clubs, events, news, resources). Each catalog supplies its own searchable
 * columns and sort vocabulary; everything else is uniform.
 */
trait FiltersCatalog
{
    /**
     * Parse and normalise the shared query parameters.
     *
     * @param  list<string>  $sorts  Allowed sort keys, in display order.
     * @return array{search: string, tag: int|null, sort: string}
     */
    protected function catalogFilters(Request $request, array $sorts, string $defaultSort): array
    {
        $sort = $request->string('sort')->value();

        return [
            'search' => trim((string) $request->string('search')),
            'tag' => $request->filled('tag') ? (int) $request->integer('tag') : null,
            'sort' => in_array($sort, $sorts, true) ? $sort : $defaultSort,
        ];
    }

    /**
     * Apply a case-insensitive OR search across the given columns. An empty
     * search term is a no-op.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $columns
     * @return Builder<*>
     */
    protected function applySearch(Builder $query, string $search, array $columns): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search, $columns): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Build the {value, label} tag options for a catalog, limited to tags
     * actually attached to its visible records via the given Tag relation.
     *
     * @param  Closure(Builder<*>): void|null  $constrain
     * @return Collection<int, array{value: string, label: string}>
     */
    protected function tagOptions(string $relation, ?Closure $constrain = null): Collection
    {
        return Tag::query()
            ->whereHas($relation, fn (Builder $query) => $constrain ? $constrain($query) : $query)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag): array => ['value' => (string) $tag->id, 'label' => $tag->name])
            ->values();
    }

    /**
     * Build the {value, label} sort options from a translation namespace.
     *
     * @param  list<string>  $sorts
     * @return list<array{value: string, label: string}>
     */
    protected function sortOptions(array $sorts, string $langKey): array
    {
        return array_map(
            fn (string $value): array => ['value' => $value, 'label' => __("{$langKey}.{$value}")],
            $sorts,
        );
    }

    /**
     * Shape the parsed filters for the Inertia `filters` prop. Mirrors the
     * shared CatalogFilterBar component, where tag is a string select value.
     *
     * @param  array{search: string, tag: int|null, sort: string}  $filters
     * @return array{search: string, tag: string, sort: string}
     */
    protected function catalogFilterProps(array $filters): array
    {
        return [
            'search' => $filters['search'],
            'tag' => $filters['tag'] !== null ? (string) $filters['tag'] : '',
            'sort' => $filters['sort'],
        ];
    }
}
