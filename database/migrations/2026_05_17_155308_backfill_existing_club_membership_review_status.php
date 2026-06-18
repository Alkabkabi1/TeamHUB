<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('club_memberships')
            ->where('status', 'pending')
            ->whereNotNull('joined_at')
            ->update([
                'status' => 'approved',
                'requested_at' => DB::raw('COALESCE(requested_at, joined_at)'),
                'reviewed_at' => DB::raw('COALESCE(reviewed_at, joined_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('club_memberships')
            ->where('status', 'approved')
            ->whereNull('reviewed_by')
            ->whereNull('rejection_reason')
            ->whereColumn('requested_at', 'joined_at')
            ->whereColumn('reviewed_at', 'joined_at')
            ->update([
                'status' => 'pending',
                'requested_at' => null,
                'reviewed_at' => null,
            ]);
    }
};
