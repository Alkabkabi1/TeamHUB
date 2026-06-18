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
        Schema::table('club_memberships', function (Blueprint $table) {
            $table->dateTime('joined_at')->nullable()->change();
            $table->string('status')->default('pending')->after('role')->index();
            $table->dateTime('requested_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('requested_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_memberships', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'status',
                'requested_at',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ]);
            $table->dateTime('joined_at')->nullable(false)->change();
        });
    }
};
