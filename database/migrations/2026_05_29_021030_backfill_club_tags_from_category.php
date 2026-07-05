<?php

use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tags') || ! Schema::hasTable('clubs')) {
            return;
        }

        $categories = DB::table('clubs')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        foreach ($categories as $index => $category) {
            $slug = Str::slug($category);

            if ($slug === '') {
                $slug = 'tag-'.($index + 1);
            }

            $tagId = DB::table('tags')->insertGetId([
                'name' => $category,
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $workspaceIds = DB::table('clubs')
                ->where('category', $category)
                ->pluck('id');

            foreach ($workspaceIds as $workspaceId) {
                DB::table('taggables')->insert([
                    'tag_id' => $tagId,
                    'taggable_id' => $workspaceId,
                    'taggable_type' => Workspace::class,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('taggables')) {
            DB::table('taggables')->truncate();
        }

        if (Schema::hasTable('tags')) {
            DB::table('tags')->truncate();
        }
    }
};
