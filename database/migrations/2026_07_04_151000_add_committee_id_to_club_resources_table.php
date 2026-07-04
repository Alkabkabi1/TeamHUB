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
        Schema::table('club_resources', function (Blueprint $table) {
            $table->foreignId('committee_id')
                ->nullable()
                ->after('club_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['club_id', 'committee_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_resources', function (Blueprint $table) {
            $table->dropIndex(['club_id', 'committee_id', 'type']);
            $table->dropForeign(['committee_id']);
            $table->dropColumn('committee_id');
        });
    }
};
