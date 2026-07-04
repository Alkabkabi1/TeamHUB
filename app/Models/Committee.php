<?php

namespace App\Models;

use App\Concerns\HasImageGallery;
use App\Concerns\HasTags;
use App\Enums\CommitteeStatus;
use Database\Factories\CommitteeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A committee (لجنة) is an optional sub-group inside a Club. It behaves like a
 * miniature club: it owns its own members, events and news, and inherits the
 * parent club's branding unless it sets its own theme.
 */
class Committee extends Model implements HasMedia
{
    /** @use HasFactory<CommitteeFactory> */
    use HasFactory;

    use HasImageGallery;
    use HasTags;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * The single-file media collection that holds the committee's logo.
     */
    public const string LOGO_COLLECTION = 'logo';

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'theme',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'status' => CommitteeStatus::class,
        ];
    }

    /**
     * Register the single-file "logo" collection plus the shared "images"
     * gallery. Uploading a new logo replaces the previous one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO_COLLECTION)->singleFile();
        $this->addMediaCollection(self::IMAGE_COLLECTION);
    }

    /**
     * Public URL for the committee logo, or null when none is set. Frontends
     * should render this attribute rather than reaching into the collection.
     *
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getFirstMediaUrl(self::LOGO_COLLECTION) ?: null);
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<ClubResource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ClubResource::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'committee_memberships');
    }

    /**
     * @return HasMany<CommitteeMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
