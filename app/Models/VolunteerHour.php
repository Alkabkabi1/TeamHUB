<?php

namespace App\Models;

use Database\Factories\VolunteerHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerHour extends Model
{
    /** @use HasFactory<VolunteerHourFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
        'event_id',
        'approved_by',
        'hours',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
