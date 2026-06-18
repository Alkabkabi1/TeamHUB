<?php

use App\Models\Club;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Move any existing club logo (logo_path) into the single-file media
     * "logo" collection, then drop the legacy column.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('clubs', 'logo_path')) {
            return;
        }

        Club::query()
            ->withTrashed()
            ->whereNotNull('logo_path')
            ->each(function (Club $club): void {
                $path = $club->getRawOriginal('logo_path');

                if ($path && Storage::disk('public')->exists($path)) {
                    $club->addMediaFromDisk($path, 'public')
                        ->preservingOriginal()
                        ->toMediaCollection(Club::LOGO_COLLECTION);
                }
            });

        Schema::table('clubs', function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('theme');
        });
    }
};
