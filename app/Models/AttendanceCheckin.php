<?php

namespace App\Models;

use Database\Factories\AttendanceCheckinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCheckin extends Model
{
    /** @use HasFactory<AttendanceCheckinFactory> */
    use HasFactory;

    protected $fillable = [
        'event_attendance_id',
        'attended_on',
        'checked_in_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_on' => 'date',
            'checked_in_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EventAttendance::class, 'event_attendance_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
