<?php

namespace App\Models;

use Database\Factories\EventAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventAttendance extends Model
{
    /** @use HasFactory<EventAttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'checked_in_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'event_attendance_id');
    }

    /**
     * Per-day presence records logged by a club Attendance Scanner.
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(AttendanceCheckin::class);
    }
}
