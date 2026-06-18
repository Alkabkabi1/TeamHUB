<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decouple certificates from event attendance: a certificate now belongs
     * directly to a user and a club, optionally references an event, and may
     * carry custom overrides for manually-issued (activity-less) certificates.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            if (! Schema::hasColumn('certificates', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('certificates', 'club_id')) {
                $table->foreignId('club_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('certificates', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('club_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('certificates', 'title')) {
                $table->string('title')->nullable()->after('event_attendance_id');
            }

            if (! Schema::hasColumn('certificates', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (! Schema::hasColumn('certificates', 'volunteer_hours')) {
                $table->decimal('volunteer_hours', 5, 2)->nullable()->after('description');
            }
        });

        // Backfill the new owner columns from the linked attendance's event.
        $rows = DB::table('certificates')
            ->join('event_attendances', 'certificates.event_attendance_id', '=', 'event_attendances.id')
            ->join('events', 'event_attendances.event_id', '=', 'events.id')
            ->select(
                'certificates.id as certificate_id',
                'event_attendances.user_id',
                'events.id as event_id',
                'events.club_id',
            )
            ->get();

        foreach ($rows as $row) {
            DB::table('certificates')->where('id', $row->certificate_id)->update([
                'user_id' => $row->user_id,
                'club_id' => $row->club_id,
                'event_id' => $row->event_id,
            ]);
        }

        // Drop the 1:1-with-attendance unique and allow attendance to be absent
        // (standalone certificates have no attendance), then enforce one
        // certificate per (user, event) while permitting many activity-less ones.
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropUnique('certificates_event_attendance_id_unique');
            $table->unsignedBigInteger('event_attendance_id')->nullable()->change();
            $table->unique(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'event_id']);
            $table->dropConstrainedForeignId('event_id');
            $table->dropConstrainedForeignId('club_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['title', 'description', 'volunteer_hours']);
            $table->unique('event_attendance_id');
        });
    }
};
