<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = Schema::hasTable('workspaces') ? 'workspaces' : 'clubs';

        if (! Schema::hasColumn($table, 'logo_path')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }

    public function down(): void
    {
        $table = Schema::hasTable('workspaces') ? 'workspaces' : 'clubs';

        Schema::table($table, function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('theme');
        });
    }
};
