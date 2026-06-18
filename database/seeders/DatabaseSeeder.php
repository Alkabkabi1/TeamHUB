<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UniversitySeeder::class,
            DemoUsersSeeder::class,
            ClubsSeeder::class,
            EventsSeeder::class,
            ClubMembershipsSeeder::class,
            ClubJoinApplicationsSeeder::class,
            ClubResourcesSeeder::class,
            EventAttendancesSeeder::class,
            VolunteerHoursSeeder::class,
            CertificateTemplateSeeder::class,
            CertificatesSeeder::class,
            PostsSeeder::class,
            CommitteesSeeder::class,
        ]);
    }
}
