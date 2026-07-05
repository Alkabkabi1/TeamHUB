<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy image_path column from posts / project_updates.
     * Media backfill is skipped on fresh installs (no legacy files).
     */
    public function up(): void
    {
        $table = Schema::hasTable('project_updates') ? 'project_updates' : 'posts';

        if (! Schema::hasColumn($table, 'image_path')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        $table = Schema::hasTable('project_updates') ? 'project_updates' : 'posts';

        Schema::table($table, function (Blueprint $table): void {
            $table->string('image_path')->nullable();
        });
    }
};
