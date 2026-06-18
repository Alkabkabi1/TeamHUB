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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('university_id');
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('university_id');
        });
    }
};
