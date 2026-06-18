<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClubJoinApplicationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applicants = User::query()
            ->where('role', 'student')
            ->whereDoesntHave('joinApplications')
            ->limit(6)
            ->get();

        $clubs = Club::query()->where('status', 'active')->where('name', '!=', 'نادي الحاسبات')->limit(3)->get();

        foreach ($applicants as $index => $user) {
            $club = $clubs[$index % $clubs->count()] ?? null;
            if (! $club) {
                continue;
            }

            if ($user->clubMemberships()->where('club_id', $club->id)->exists()) {
                continue;
            }

            ClubJoinApplication::factory()->pending()->create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'full_name' => $user->name,
                'university_email' => $user->email,
            ]);
        }

        $csClub = Club::query()->where('name', 'نادي الحاسبات')->first();
        $supervisor = User::query()->where('email', 'club-leader@uqu.edu.sa')->first();

        if (! $csClub) {
            return;
        }

        $reviewedApplicants = User::query()
            ->where('role', 'student')
            ->whereDoesntHave('joinApplications', fn ($query) => $query->where('club_id', $csClub->id))
            ->limit(4)
            ->get();

        foreach ($reviewedApplicants->take(2) as $user) {
            ClubJoinApplication::factory()->rejected()->create([
                'user_id' => $user->id,
                'club_id' => $csClub->id,
                'full_name' => $user->name,
                'university_email' => $user->email,
                'reviewed_by' => $supervisor?->id,
            ]);
        }

        foreach ($reviewedApplicants->skip(2)->take(2) as $user) {
            ClubJoinApplication::factory()->approved()->create([
                'user_id' => $user->id,
                'club_id' => $csClub->id,
                'full_name' => $user->name,
                'university_email' => $user->email,
                'reviewed_by' => $supervisor?->id,
            ]);
        }

        // Pending applications for the supervisor's club so the dashboard shows
        // real join requests to approve/reject. Skip current members and anyone
        // who already has an application on this club.
        $pendingApplicants = User::query()
            ->where('role', 'student')
            ->where('email', '!=', 'student@uqu.edu.sa')
            ->whereDoesntHave('joinApplications', fn ($query) => $query->where('club_id', $csClub->id))
            ->whereDoesntHave('clubMemberships', fn ($query) => $query->where('club_id', $csClub->id))
            ->limit(3)
            ->get();

        foreach ($pendingApplicants as $user) {
            ClubJoinApplication::factory()->pending()->create([
                'user_id' => $user->id,
                'club_id' => $csClub->id,
                'full_name' => $user->name,
                'university_email' => $user->email,
            ]);
        }
    }
}
