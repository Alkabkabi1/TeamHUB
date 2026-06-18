<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-day attendance records. An event_attendance row is the student's
     * registration (one per user+event); each check-in here marks their
     * presence on a single calendar day, so a multi-day activity accrues one
     * row per day attended.
     */
    public function up(): void
    {
        Schema::create('attendance_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_attendance_id')->constrained()->cascadeOnDelete();
            $table->date('attended_on');
            $table->dateTime('checked_in_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_attendance_id', 'attended_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_checkins');
    }
};
