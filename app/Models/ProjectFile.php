<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    use HasFactory;

    public const TYPE_DOWNLOAD = 'download';

    public const TYPE_MEDIA = 'media';

    protected $table = 'project_files';

    protected $fillable = [
        'workspace_id',
        'project_id',
        'type',
        'title',
        'description',
        'format',
        'access',
        'file_path',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The project this file belongs to, or null for a workspace-level file.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  Builder<ProjectFile>  $query
     * @return Builder<ProjectFile>
     */
    public function scopeDownloads(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DOWNLOAD);
    }

    /**
     * @param  Builder<ProjectFile>  $query
     * @return Builder<ProjectFile>
     */
    public function scopeMedia(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MEDIA);
    }

    /**
     * @param  Builder<ProjectFile>  $query
     * @return Builder<ProjectFile>
     */
    public function scopeForProject(Builder $query, Project $project): Builder
    {
        return $query->where('project_id', $project->id);
    }
}
