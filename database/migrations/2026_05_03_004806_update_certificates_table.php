<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCertificatesTable extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {

            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');

            $table->foreignId('event_attendance_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {

            $table->dropForeign(['event_attendance_id']);
            $table->dropColumn('event_attendance_id');

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }
}
