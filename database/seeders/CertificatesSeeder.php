<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class CertificatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EventAttendance::query()
            ->whereIn('status', ['approved', 'checked_in'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '<', now()))
            ->limit(25)
            ->get()
            ->each(function (EventAttendance $attendance): void {
                Certificate::firstOrCreate(
                    ['event_attendance_id' => $attendance->id],
                    ['file_path' => 'certificates/'.$attendance->id.'.pdf']
                );
            });

        $this->seedDemoStudentCertificate();
    }

    private function seedDemoStudentCertificate(): void
    {
        $student = User::query()->where('email', 'student@uqu.edu.sa')->first();

        if (! $student) {
            return;
        }

        $attendance = EventAttendance::query()
            ->where('user_id', $student->id)
            ->whereIn('status', ['approved', 'checked_in'])
            ->whereHas('event', fn ($query) => $query->where('starts_at', '<', now()))
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

            $attendance = EventAttendance::factory()->checkedIn()->create([
                'user_id' => $student->id,
                'event_id' => $event->id,
                'checked_in_at' => $event->starts_at,
            ]);
        }

        Certificate::firstOrCreate(
            ['event_attendance_id' => $attendance->id],
            ['file_path' => 'certificates/demo-student-'.$attendance->id.'.pdf']
        );
    }
}
