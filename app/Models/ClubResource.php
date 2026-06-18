<?php

namespace App\Models;

use App\Concerns\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubResource extends Model
{
    use HasFactory;
    use HasTags;

    public const TYPE_DOWNLOAD = 'download';

    public const TYPE_MEDIA = 'media';

    protected $fillable = [
        'club_id',
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

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @param  Builder<ClubResource>  $query
     * @return Builder<ClubResource>
     */
    public function scopeDownloads(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DOWNLOAD);
    }

    /**
     * @param  Builder<ClubResource>  $query
     * @return Builder<ClubResource>
     */
    public function scopeMedia(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MEDIA);
    }
}
