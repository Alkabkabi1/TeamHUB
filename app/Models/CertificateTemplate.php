<?php

namespace App\Models;

use Database\Factories\CertificateTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CertificateTemplate extends Model implements HasMedia
{
    /** @use HasFactory<CertificateTemplateFactory> */
    use HasFactory;

    use InteractsWithMedia;

    /**
     * The single-file media collection that holds the background image.
     */
    public const string TEMPLATE_COLLECTION = 'template';

    protected $fillable = [
        'club_id',
        'name',
        'is_default',
        'status',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Register the single-file "template" media collection. Uploading a new
     * background image replaces the previous one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::TEMPLATE_COLLECTION)->singleFile();
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return HasMany<CertificatePlaceholder, $this>
     */
    public function placeholders(): HasMany
    {
        return $this->hasMany(CertificatePlaceholder::class)->orderBy('sort');
    }

    /**
     * Scope: only templates marked active (ready to issue from).
     *
     * @param  Builder<CertificateTemplate>  $query
     * @return Builder<CertificateTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Public URL for the background image, or null when none is set.
     */
    public function imageUrl(): ?string
    {
        return $this->getFirstMediaUrl(self::TEMPLATE_COLLECTION) ?: null;
    }

    /**
     * Absolute filesystem path to the background image, for server-side PDF
     * rendering. Null when no image is attached.
     */
    public function imagePath(): ?string
    {
        return $this->getFirstMedia(self::TEMPLATE_COLLECTION)?->getPath();
    }
}
