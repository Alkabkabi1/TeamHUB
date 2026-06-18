<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy free-text `role` column is fully superseded by the
 * `club_membership_roles` pivot (named ClubRole values). Every reader uses the
 * pivot, so this drops the vestigial column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('club_memberships', 'role')) {
            Schema::table('club_memberships', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('club_memberships', 'role')) {
            Schema::table('club_memberships', function (Blueprint $table) {
                $table->string('role')->nullable()->after('club_id');
            });
        }
    }
};
