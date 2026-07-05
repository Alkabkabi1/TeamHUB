<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUsersSeeder::class,
            WorkspacesSeeder::class,
            WorkspaceMembershipsSeeder::class,
            WorkspaceMembershipRequestsSeeder::class,
            ProjectFilesSeeder::class,
            ProjectUpdatesSeeder::class,
            ProjectsSeeder::class,
        ]);
    }
}
