<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Each user carries an opaque, revocable token encoded into their personal
     * attendance QR code. A club Attendance Scanner resolves a student by
     * scanning this token.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('remember_token');
        });

        DB::table('users')->whereNull('qr_token')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['qr_token' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
