<?php

namespace App\Models;

use App\Concerns\HasImageGallery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectUpdate extends Model implements HasMedia
{
    use HasFactory;
    use HasImageGallery;
    use InteractsWithMedia;

    protected $table = 'project_updates';

    protected $fillable = [
        'title',
        'body',
        'published_at',
        'workspace_id',
        'project_id',
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

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The project this update belongs to, or null for a workspace-level update.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
