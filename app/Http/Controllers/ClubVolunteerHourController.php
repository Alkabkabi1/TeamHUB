<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVolunteerHourRequest;
use App\Models\Club;
use App\Models\VolunteerHour;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ClubVolunteerHourController extends Controller
{
    /**
     * Store or update volunteer hours for a student who attended a past event.
     */
    public function store(StoreVolunteerHourRequest $request, Club $club): RedirectResponse
    {
        $validated = $request->validated();
        $eventId = $validated['event_id'] ?? null;

        $attributes = [
            'club_id' => $club->id,
            'hours' => $validated['hours'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ];

        if ($eventId !== null) {
            VolunteerHour::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'event_id' => $eventId,
                ],
                $attributes,
            );
        } else {
            VolunteerHour::create(array_merge($attributes, [
                'user_id' => $validated['user_id'],
                'event_id' => null,
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('volunteer_hours.recorded'),
        ]);

        return redirect()->route('clubs.manage', $club);
    }
}
