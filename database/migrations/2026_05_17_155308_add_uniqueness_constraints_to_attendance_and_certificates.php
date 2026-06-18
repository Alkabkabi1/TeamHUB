<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'event_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->unique('event_attendance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(['event_attendance_id']);
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'event_id']);
        });
    }
};
