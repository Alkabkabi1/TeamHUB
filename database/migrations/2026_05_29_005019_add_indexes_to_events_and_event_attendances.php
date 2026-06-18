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
        Schema::table('events', function (Blueprint $table) {
            // Public catalog filters on status + orders/filters by start time.
            $table->index(['status', 'starts_at']);
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            // The unique (user_id, event_id) pair already exists from the
            // 2026_05_17 uniqueness-constraints migration; only add the
            // capacity/registration count index here (event + status).
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'starts_at']);
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'status']);
        });
    }
};
