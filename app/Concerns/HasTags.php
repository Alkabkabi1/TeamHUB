<?php

namespace App\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Adds a polymorphic many-to-many relationship to the shared tags taxonomy.
 */
trait HasTags
{
    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Limit the query to records carrying the given tag. A null id is a no-op
     * so callers can pass the (optional) request filter straight through.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithTag(Builder $query, ?int $tagId): Builder
    {
        return $query->when(
            $tagId,
            fn (Builder $query): Builder => $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tagId)),
        );
    }
}
