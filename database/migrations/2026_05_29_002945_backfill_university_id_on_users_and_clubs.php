<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Assign any university-less users and clubs to the first (default)
     * university, so the staff Filament panel can scope data by tenancy.
     * No-op on a fresh database (seeders set university_id directly).
     */
    public function up(): void
    {
        $universityId = DB::table('universities')->orderBy('id')->value('id');

        if ($universityId === null) {
            return;
        }

        DB::table('users')->whereNull('university_id')->update(['university_id' => $universityId]);
        DB::table('clubs')->whereNull('university_id')->update(['university_id' => $universityId]);
    }

    /**
     * Reverse the migrations. Intentionally irreversible — the original
     * (null) tenancy assignment is not worth restoring.
     */
    public function down(): void
    {
        //
    }
};
