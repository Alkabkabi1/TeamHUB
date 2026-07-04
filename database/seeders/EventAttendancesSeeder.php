<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class EventAttendancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::query()->where('role', 'student')->limit(12)->get();

        // Reserve one past event of the supervisor's club with attendees who
        // registered but were never checked in, so "taking attendance" (marking
        // attendees present) can be exercised on real, still-open data.
        $this->seedAttendancePendingEvent($students);

        $pastEvents = Event::query()->where('starts_at', '<', now())->limit(10)->get();
        $this->seedPastEventAttendances($pastEvents, $students);

        $upcomingEvents = Event::query()
            ->where('starts_at', '>=', now())
            ->where('status', 'active')
            ->limit(5)
            ->get();
        $this->seedUpcomingEventAttendances($upcomingEvents, $students);

        $this->seedDemoStudentPastAttendance($pastEvents);

        $this->seedCheckins();
    }

    /**
     * Backfill per-day attendance check-ins for already checked-in attendances,
     * recording the presence rows a scanner would create. A couple of attendees
     * get a second day to demonstrate multi-day activities.
     */
    private function seedCheckins(): void
    {
        $scanner = User::query()->where('email', 'scanner@teamhub.test')->first();

        $attendances = EventAttendance::query()
            ->where('status', 'checked_in')
            ->whereDoesntHave('checkins')
            ->with('event:id,starts_at')
            ->get();

        foreach ($attendances as $index => $attendance) {
            $startsAt = $attendance->event?->starts_at;

            if ($startsAt === null) {
                continue;
            }

            $attendance->checkins()->create([
                'attended_on' => $startsAt->toDateString(),
                'checked_in_at' => $startsAt,
                'recorded_by' => $scanner?->id,
            ]);

            // Give a slice of attendees a second day to exercise multi-day data.
            if ($index % 4 === 0) {
                $attendance->checkins()->create([
                    'attended_on' => $startsAt->copy()->addDay()->toDateString(),
                    'checked_in_at' => $startsAt->copy()->addDay(),
                    'recorded_by' => $scanner?->id,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Event>  $pastEvents
     * @param  Collection<int, User>  $students
     */
    private function seedPastEventAttendances(Collection $pastEvents, Collection $students): void
    {
        foreach ($pastEvents as $event) {
            // Idempotent: leave events that already have attendances untouched so
            // re-seeding does not keep adding randomly-picked attendees.
            if (EventAttendance::query()->where('event_id', $event->id)->exists()) {
                continue;
            }

            $attendees = $students->random(min(fake()->numberBetween(3, 8), $students->count()));

            foreach ($attendees as $student) {
                $attributes = EventAttendance::factory()
                    ->checkedIn()
                    ->make([
                        'user_id' => $student->id,
                        'event_id' => $event->id,
                        'checked_in_at' => $event->starts_at,
                    ])
                    ->getAttributes();

                EventAttendance::firstOrCreate(
                    ['user_id' => $student->id, 'event_id' => $event->id],
                    [
                        'status' => $attributes['status'],
                        'checked_in_at' => $attributes['checked_in_at'],
                    ]
                );
            }
        }
    }

    /**
     * @param  Collection<int, Event>  $upcomingEvents
     * @param  Collection<int, User>  $students
     */
    private function seedUpcomingEventAttendances(Collection $upcomingEvents, Collection $students): void
    {
        foreach ($upcomingEvents as $event) {
            // Idempotent: skip events that already have attendances (see above).
            if (EventAttendance::query()->where('event_id', $event->id)->exists()) {
                continue;
            }

            $attendees = $students->random(min(fake()->numberBetween(2, 4), $students->count()));

            foreach ($attendees as $student) {
                $factory = fake()->boolean()
                    ? EventAttendance::factory()->approved()
                    : EventAttendance::factory()->pending();

                $attributes = $factory
                    ->make([
                        'user_id' => $student->id,
                        'event_id' => $event->id,
                    ])
                    ->getAttributes();

                EventAttendance::firstOrCreate(
                    ['user_id' => $student->id, 'event_id' => $event->id],
                    [
                        'status' => $attributes['status'],
                        'checked_in_at' => $attributes['checked_in_at'],
                    ]
                );
            }
        }
    }

    /**
     * Seed the most recent past event of the supervisor's club (نادي الحاسبات)
     * with attendees in the "registered" state — i.e. they signed up but have
     * not been checked in yet. This is the data a supervisor needs to test
     * taking attendance for a finished activity.
     *
     * @param  Collection<int, User>  $students
     */
    private function seedAttendancePendingEvent(Collection $students): void
    {
        $club = Club::query()->where('name', 'نادي الحاسبات')->first();

        if ($club === null) {
            return;
        }

        $event = Event::query()
            ->where('club_id', $club->id)
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->first();

        // Idempotent: leave the event untouched once it already has attendances.
        if ($event === null || EventAttendance::query()->where('event_id', $event->id)->exists()) {
            return;
        }

        $attendees = $students->random(min(6, $students->count()));

        foreach ($attendees as $student) {
            EventAttendance::firstOrCreate(
                ['user_id' => $student->id, 'event_id' => $event->id],
                ['status' => 'registered', 'checked_in_at' => null]
            );
        }
    }

    /**
     * @param  Collection<int, Event>  $pastEvents
     */
    private function seedDemoStudentPastAttendance(Collection $pastEvents): void
    {
        $student = User::query()->where('email', 'student@teamhub.test')->first();
        $event = $pastEvents->first();

        if (! $student || ! $event) {
            return;
        }

        $attributes = EventAttendance::factory()
            ->checkedIn()
            ->make([
                'user_id' => $student->id,
                'event_id' => $event->id,
                'checked_in_at' => $event->starts_at,
            ])
            ->getAttributes();

        EventAttendance::firstOrCreate(
            ['user_id' => $student->id, 'event_id' => $event->id],
            [
                'status' => $attributes['status'],
                'checked_in_at' => $attributes['checked_in_at'],
            ]
        );
    }
}
