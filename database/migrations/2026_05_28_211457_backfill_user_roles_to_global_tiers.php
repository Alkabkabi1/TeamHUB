<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Collapse legacy global roles to the two supported tiers. A former
     * "club_supervisor" is globally a student; their supervisory authority now
     * comes from a club-scoped manager role (see club_membership_roles).
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotIn('role', ['student', 'university_staff'])
            ->update(['role' => 'student']);
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally irreversible — the original per-user legacy role value is
     * not recoverable once collapsed.
     */
    public function down(): void
    {
        //
    }
};
