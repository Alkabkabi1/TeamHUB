<?php

use App\Enums\ClubRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the named club-role pivot from each existing membership's legacy
     * `role`, so historical memberships carry the capabilities their role implies.
     */
    public function up(): void
    {
        $now = now();

        DB::table('club_memberships')
            ->select('id', 'role')
            ->orderBy('id')
            ->chunkById(500, function ($memberships) use ($now): void {
                $rows = $memberships->map(fn ($membership): array => [
                    'club_membership_id' => $membership->id,
                    'role' => ClubRole::fromLegacy($membership->role)->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('club_membership_roles')->insertOrIgnore($rows);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('club_membership_roles')->truncate();
    }
};
