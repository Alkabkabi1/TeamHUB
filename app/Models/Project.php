<?php

namespace App\Models;

use App\Concerns\HasImageGallery;
use App\Enums\ProjectStatus;
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
 * A project is a focused team effort inside a workspace. It owns its own
 * members, tasks, files, and updates, and inherits the parent workspace's
 * branding unless it sets its own theme.
 */
class Project extends Model implements HasMedia
{
    use HasFactory;
    use HasImageGallery;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * The single-file media collection that holds the project's logo.
     */
    public const string LOGO_COLLECTION = 'logo';

    protected $table = 'projects';

    protected $fillable = [
        'workspace_id',
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
            'status' => ProjectStatus::class,
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
     * Public URL for the project logo, or null when none is set. Frontends
     * should render this attribute rather than reaching into the collection.
     *
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getFirstMediaUrl(self::LOGO_COLLECTION) ?: null);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<ProjectUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    /**
     * @return HasMany<ProjectFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_memberships');
    }

    /**
     * @return HasMany<ProjectMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
