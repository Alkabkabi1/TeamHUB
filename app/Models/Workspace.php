<?php

namespace App\Models;

use App\Enums\WorkspaceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Workspace extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * The single-file media collection that holds the workspace logo.
     */
    public const string LOGO_COLLECTION = 'logo';

    protected $table = 'workspaces';

    protected $fillable = [
        'name',
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
            'status' => WorkspaceStatus::class,
        ];
    }

    /**
     * Register the single-file "logo" media collection. Uploading a new logo
     * replaces the previous one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO_COLLECTION)->singleFile();
    }

    /**
     * Whether the workspace can be permanently deleted — only when it has no
     * memberships, so no participation history would be lost.
     */
    public function canBeForceDeleted(): bool
    {
        return ! $this->memberships()->exists();
    }

    /**
     * Public URL for the workspace logo, or null when none is set. Frontends
     * should render this attribute rather than reaching into the media collection.
     *
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getFirstMediaUrl(self::LOGO_COLLECTION) ?: null);
    }

    /**
     * Absolute filesystem path to the logo, for server-side rendering such as
     * embedding the logo in a generated PDF. Null when no logo is set.
     */
    public function logoPath(): ?string
    {
        return $this->getFirstMedia(self::LOGO_COLLECTION)?->getPath();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_memberships');
    }

    /**
     * @return HasMany<WorkspaceMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    /**
     * @return HasMany<WorkspaceMembershipRequest, $this>
     */
    public function membershipRequests(): HasMany
    {
        return $this->hasMany(WorkspaceMembershipRequest::class);
    }

    /**
     * @return HasMany<ProjectFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * @return HasMany<ProjectUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
