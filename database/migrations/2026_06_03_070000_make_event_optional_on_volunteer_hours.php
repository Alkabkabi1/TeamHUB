<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decouple volunteer hours from events: hours now belong directly to a club
     * and a member, optionally referencing an event. This lets supervisors (and
     * the AI assistant) award hours without tying them to a specific activity.
     */
    public function up(): void
    {
        Schema::table('volunteer_hours', function (Blueprint $table): void {
            if (! Schema::hasColumn('volunteer_hours', 'club_id')) {
                $table->foreignId('club_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            }
        });

        // Backfill club_id from each row's linked event so existing event-based
        // hours stay scoped to their club once summaries switch to club_id.
        DB::table('volunteer_hours')
            ->whereNull('club_id')
            ->whereNotNull('event_id')
            ->update([
                'club_id' => DB::raw('(select events.club_id from events where events.id = volunteer_hours.event_id)'),
            ]);

        // The linked event is now optional. The existing unique(user_id, event_id)
        // index permits many activity-less rows per member (NULLs repeat freely).
        Schema::table('volunteer_hours', function (Blueprint $table): void {
            $table->unsignedBigInteger('event_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_hours', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('club_id');
        });
    }
};
