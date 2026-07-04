<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Database\Seeder;

class VolunteerHoursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supervisor = User::query()->where('email', 'club-leader@teamhub.test')->first();

        EventAttendance::query()
            ->whereIn('status', ['approved', 'checked_in'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '<', now()))
            ->with('event')
            ->orderBy('id')
            ->limit(40)
            ->get()
            ->each(function (EventAttendance $attendance) use ($supervisor): void {
                VolunteerHour::firstOrCreate(
                    ['user_id' => $attendance->user_id, 'event_id' => $attendance->event_id],
                    [
                        'hours' => fake()->randomFloat(2, 2, 8),
                        'approved_by' => $supervisor?->id,
                        'approved_at' => $attendance->event?->ends_at ?? now(),
                    ]
                );
            });

        $this->seedDemoStudentVolunteerHours($supervisor);
    }

    private function seedDemoStudentVolunteerHours(?User $supervisor): void
    {
        $student = User::query()->where('email', 'student@teamhub.test')->first();

        if (! $student) {
            return;
        }

        $attendance = EventAttendance::query()
            ->where('user_id', $student->id)
            ->whereIn('status', ['approved', 'checked_in'])
            ->whereHas('event', fn ($query) => $query->where('starts_at', '<', now()))
            ->with('event')
            ->first();

        if (! $attendance) {
            $club = Club::query()->where('name', 'نادي الحاسبات')->first()
                ?? $student->clubMemberships()->first()?->club;

            if (! $club) {
                return;
            }

            $event = Event::query()
                ->where('club_id', $club->id)
                ->where('starts_at', '<', now())
                ->where('status', 'active')
                ->orderByDesc('starts_at')
                ->first()
                ?? Event::factory()->past()->for($club)->create(['status' => 'active']);

            $attendance = EventAttendance::updateOrCreate(
                ['user_id' => $student->id, 'event_id' => $event->id],
                [
                    ...EventAttendance::factory()->checkedIn()->raw([
                        'user_id' => $student->id,
                        'event_id' => $event->id,
                        'checked_in_at' => $event->starts_at,
                    ]),
                ]
            );
        }

        VolunteerHour::firstOrCreate(
            ['user_id' => $student->id, 'event_id' => $attendance->event_id],
            [
                'hours' => 4.5,
                'approved_by' => $supervisor?->id,
                'approved_at' => $attendance->event?->ends_at ?? now(),
            ]
        );
    }
}
