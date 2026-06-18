<?php

namespace Database\Seeders;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClubMembershipsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubLeader = User::query()->where('email', 'club-leader@uqu.edu.sa')->first();
        $committeeLeader = User::query()->where('email', 'committee-leader@uqu.edu.sa')->first();
        $scanner = User::query()->where('email', 'scanner@uqu.edu.sa')->first();
        $student = User::query()->where('email', 'student@uqu.edu.sa')->first();
        $member = User::query()->where('email', 'member@uqu.edu.sa')->first();
        $csClub = Club::query()->where('name', 'نادي الحاسبات')->first();

        // The demo club leader: ClubLead of the CS club...
        if ($clubLeader && $csClub) {
            $joinedAt = now()->subYears(2);

            $this->seedMembership($clubLeader, $csClub, [ClubRole::ClubLead], $joinedAt);
        }

        // An ordinary CS-club member who leads a committee only (see
        // CommitteesSeeder) — holds no club-level management role here.
        if ($committeeLeader && $csClub) {
            $this->seedMembership($committeeLeader, $csClub, [ClubRole::Member], now()->subYear());
        }

        // A dedicated Attendance Scanner of the CS club: can scan QR codes to
        // log attendance, but holds no other management capability.
        if ($scanner && $csClub) {
            $this->seedMembership($scanner, $csClub, [ClubRole::AttendanceScanner], now()->subYear());
        }

        // A plain CS-club member (also used as a committee member).
        if ($member && $csClub) {
            $this->seedMembership($member, $csClub, [ClubRole::Member], now()->subMonths(8));
        }

        // ...and a plain student member of a second club, proving a user can
        // manage one club while being an ordinary member of another.
        $environmentClub = Club::query()->where('name', 'نادي البيئة')->first();
        if ($clubLeader && $environmentClub) {
            $this->seedMembership($clubLeader, $environmentClub, [ClubRole::Member], now()->subYear());
        }

        if ($student) {
            foreach (['نادي الحاسبات', 'نادي البيئة', 'نادي الفنون'] as $clubName) {
                $club = Club::query()->where('name', $clubName)->first();
                if ($club) {
                    $joinedAt = now()->subMonths(fake()->numberBetween(6, 24));
                    $this->seedMembership($student, $club, [ClubRole::Member], $joinedAt);
                }
            }
        }

        // Exclude the fixed demo accounts: their club roles are seeded
        // explicitly above and must not be polluted by the random assignment.
        $students = User::query()
            ->where('role', 'student')
            ->whereNotIn('email', [
                'student@uqu.edu.sa',
                'member@uqu.edu.sa',
                'club-leader@uqu.edu.sa',
                'committee-leader@uqu.edu.sa',
                'scanner@uqu.edu.sa',
            ])
            ->limit(12)
            ->get();
        $activeClubs = Club::query()->where('status', 'active')->get();

        foreach ($activeClubs as $club) {
            $memberCount = $club->name === 'نادي الحاسبات' ? 8 : fake()->numberBetween(3, 6);
            $picked = $students->random(min($memberCount, $students->count()));

            foreach ($picked as $user) {
                $joinedAt = now()->subMonths(fake()->numberBetween(1, 36));
                $roles = fake()->boolean(25) ? [ClubRole::EventsManager] : [ClubRole::Member];

                $this->seedMembership($user, $club, $roles, $joinedAt);
            }
        }

        if ($csClub) {
            $pendingStudents = User::query()
                ->where('role', 'student')
                ->where('email', '!=', 'student@uqu.edu.sa')
                ->whereDoesntHave('clubMemberships', fn ($query) => $query->where('club_id', $csClub->id))
                ->limit(3)
                ->get();

            foreach ($pendingStudents as $user) {
                ClubMembership::factory()->pending()->create([
                    'user_id' => $user->id,
                    'club_id' => $csClub->id,
                ]);
            }
        }
    }

    /**
     * Idempotently create an approved membership and grant its named roles
     * (always including the baseline Member role) via the pivot.
     *
     * @param  array<int, ClubRole>  $roles
     */
    private function seedMembership(User $user, Club $club, array $roles, \DateTimeInterface $joinedAt): void
    {
        $membership = ClubMembership::firstOrCreate(
            ['user_id' => $user->id, 'club_id' => $club->id],
            [
                'status' => 'approved',
                'requested_at' => $joinedAt,
                'reviewed_at' => $joinedAt,
                'joined_at' => $joinedAt,
            ],
        );

        if (! in_array(ClubRole::Member, $roles, true)) {
            $roles[] = ClubRole::Member;
        }

        foreach ($roles as $role) {
            $membership->assignClubRole($role);
        }
    }
}
