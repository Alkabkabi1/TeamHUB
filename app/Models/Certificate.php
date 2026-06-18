<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
        'certificate_template_id',
        'event_id',
        'event_attendance_id',
        'title',
        'description',
        'volunteer_hours',
        'file_path',
        'certificate_no',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'volunteer_hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The template this certificate was rendered from, when chosen explicitly.
     *
     * @return BelongsTo<CertificateTemplate, $this>
     */
    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    /**
     * The attendance this certificate was issued from, when activity-based.
     *
     * @return BelongsTo<EventAttendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EventAttendance::class, 'event_attendance_id');
    }

    /**
     * Scope: certificates older than 1 year (by issued_at).
     *
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('issued_at', '<', now()->subYear());
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate): void {
            if (empty($certificate->certificate_no)) {
                $certificate->certificate_no = 'CERT-'.strtoupper(Str::random(10));
            }

            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }

            // Activity-based certificates may be created with only an attendance
            // reference (e.g. the factory); derive the owner columns from it so
            // the user/club/event relationships resolve consistently.
            if (empty($certificate->user_id) && $certificate->event_attendance_id !== null) {
                $attendance = EventAttendance::with('event')->find($certificate->event_attendance_id);

                if ($attendance !== null) {
                    $certificate->user_id = $attendance->user_id;
                    $certificate->event_id ??= $attendance->event_id;
                    $certificate->club_id ??= $attendance->event?->club_id;
                }
            }
        });
    }
}
