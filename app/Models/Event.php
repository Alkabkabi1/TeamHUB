<?php

namespace App\Models;

use App\Concerns\HasImageGallery;
use App\Concerns\HasTags;
use App\Enums\EventAttendanceStatus;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends Model implements HasMedia
{
    use HasFactory;
    use HasImageGallery;
    use HasTags;
    use InteractsWithMedia;

    protected $fillable = [
        'club_id',
        'committee_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
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
     * The committee this event belongs to, or null for a club-level event.
     *
     * @return BelongsTo<Committee, $this>
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function volunteerHours(): HasMany
    {
        return $this->hasMany(VolunteerHour::class);
    }

    /**
     * Events that have not yet started.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    /**
     * Events whose start time is in the past.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now());
    }

    /**
     * Events that are active (publicly listed and open for registration).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Active->value);
    }

    /**
     * Whether the event has already started.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at?->isPast() ?? false;
    }

    /**
     * Whether the event is open for new registrations.
     */
    public function isOpenForRegistration(): bool
    {
        return $this->status === EventStatus::Active && ! $this->hasStarted();
    }

    /**
     * Whether attendance can currently be scanned: the activity is active and
     * has not yet finished (ongoing or upcoming). A multi-day activity stays
     * scannable until its end.
     */
    public function isScannable(): bool
    {
        if ($this->status !== EventStatus::Active) {
            return false;
        }

        if ($this->ends_at !== null) {
            return $this->ends_at->greaterThanOrEqualTo(now());
        }

        return $this->starts_at !== null
            && $this->starts_at->greaterThanOrEqualTo(now()->startOfDay());
    }

    /**
     * Number of seats currently occupied (registered, approved or checked in).
     */
    public function registeredCount(): int
    {
        return $this->attendances()
            ->whereIn('status', EventAttendanceStatus::registeredValues())
            ->count();
    }

    /**
     * Whether the event has reached its capacity. Events with no capacity set
     * are never considered full.
     */
    public function isFull(): bool
    {
        return $this->capacity !== null
            && $this->registeredCount() >= $this->capacity;
    }
}
