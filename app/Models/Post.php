<?php

namespace App\Models;

use App\Concerns\HasImageGallery;
use App\Concerns\HasTags;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasImageGallery;
    use HasTags;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'body',
        'published_at',
        'club_id',
        'committee_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Register the shared "images" gallery collection.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGE_COLLECTION);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * The committee this post belongs to, or null for a club-level post.
     *
     * @return BelongsTo<Committee, $this>
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
