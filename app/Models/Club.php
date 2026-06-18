<?php

namespace App\Models;

use App\Concerns\HasTags;
use App\Enums\ClubStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Club extends Model implements HasMedia
{
    use HasFactory, HasTags, InteractsWithMedia, SoftDeletes;

    /**
     * The single-file media collection that holds the club's logo.
     */
    public const string LOGO_COLLECTION = 'logo';

    protected $fillable = [
        'name',
        'theme',
        'category',
        'college',
        'status',
        'university_id',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'status' => ClubStatus::class,
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
     * Whether the club can be permanently deleted — only when it has no events
     * or memberships, so no participation history would be lost.
     */
    public function canBeForceDeleted(): bool
    {
        return ! $this->events()->exists() && ! $this->memberships()->exists();
    }

    /**
     * Public URL for the club logo, or null when none is set. Frontends should
     * render this attribute rather than reaching into the media collection.
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

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'club_memberships');
    }

    public function memberships()
    {
        return $this->hasMany(ClubMembership::class);
    }

    public function joinApplications()
    {
        return $this->hasMany(ClubJoinApplication::class);
    }

    public function resources()
    {
        return $this->hasMany(ClubResource::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function committees()
    {
        return $this->hasMany(Committee::class);
    }

    public function certificateTemplates()
    {
        return $this->hasMany(CertificateTemplate::class);
    }

    /**
     * The club's active default certificate template, used when issuing
     * certificates. Null when the club has not configured one yet.
     */
    public function defaultCertificateTemplate(): ?CertificateTemplate
    {
        return $this->certificateTemplates()
            ->active()
            ->where('is_default', true)
            ->first();
    }
}
