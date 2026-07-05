<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared search and sort filtering for list pages.
 */
trait FiltersCatalog
{
    /**
     * @param  list<string>  $sorts
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
