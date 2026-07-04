<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;

class UniversitySeeder extends Seeder
{
    /**
     * Seed the default (single) university. Multi-university support is
     * forthcoming; the schema is tenancy-ready via a nullable university_id.
     */
    public function run(): void
    {
        University::firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'المنظمة الافتراضية'],
        );
    }
}
