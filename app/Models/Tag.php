<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if (($tag->slug === null || $tag->slug === '') && $tag->name !== null) {
                $tag->slug = Str::slug($tag->name) ?: 'tag-'.Str::random(8);
            }
        });
    }

    /**
     * @return MorphToMany<Club, $this>
     */
    public function clubs(): MorphToMany
    {
        return $this->morphedByMany(Club::class, 'taggable');
    }

    /**
     * @return MorphToMany<Event, $this>
     */
    public function events(): MorphToMany
    {
        return $this->morphedByMany(Event::class, 'taggable');
    }

    /**
     * @return MorphToMany<Post, $this>
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    /**
     * @return MorphToMany<ClubResource, $this>
     */
    public function resources(): MorphToMany
    {
        return $this->morphedByMany(ClubResource::class, 'taggable');
    }
}
