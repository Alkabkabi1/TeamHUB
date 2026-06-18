<?php

use App\Models\Club;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Seed the new tags taxonomy from the existing single-value `category`
     * column so the clubs catalog has tags to filter by out of the box. The
     * `category` column itself is left in place (still used by events, the
     * home page, the club detail page and the admin panel).
     */
    public function up(): void
    {
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

            $clubIds = DB::table('clubs')
                ->where('category', $category)
                ->pluck('id');

            foreach ($clubIds as $clubId) {
                DB::table('taggables')->insert([
                    'tag_id' => $tagId,
                    'taggable_id' => $clubId,
                    'taggable_type' => Club::class,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('taggables')->truncate();
        DB::table('tags')->truncate();
    }
};
