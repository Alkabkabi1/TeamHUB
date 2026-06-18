<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostsSeeder extends Seeder
{
    /**
     * Whether to attach generated cover images to seeded posts. Disabled for
     * now so news cards render with the clean placeholder, ready for real
     * uploads; flip to true to re-enable (PostFactory::withImages and
     * DemoCoverImage are kept for that).
     */
    private const SEED_IMAGES = false;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cover every active club so the public news feed is well populated,
        // not just a handful of articles from three clubs.
        $clubs = Club::query()->where('status', 'active')->get();

        if ($clubs->isEmpty()) {
            return;
        }

        $fallbackAuthorId = User::query()->where('role', 'student')->value('id');

        foreach ($clubs as $club) {
            // Idempotent: skip if club already has posts.
            if (Post::where('club_id', $club->id)->exists()) {
                continue;
            }

            $authorId = $this->resolveAuthorId($club, $fallbackAuthorId);

            if ($authorId === null) {
                continue;
            }

            $factory = self::SEED_IMAGES ? Post::factory()->withImages() : Post::factory();

            $posts = $factory
                ->count(fake()->numberBetween(2, 3))
                ->create([
                    'club_id' => $club->id,
                    'user_id' => $authorId,
                ]);

            // Tag each post with its club's tags so the news catalog has tags
            // to filter by out of the box.
            $tagIds = $club->tags()->pluck('tags.id');
            $posts->each(fn (Post $post) => $post->tags()->syncWithoutDetaching($tagIds));
        }
    }

    /**
     * Pick a real, approved member of the club as the article author so posts
     * are attributed to an actual person rather than a throwaway factory user.
     */
    private function resolveAuthorId(Club $club, ?int $fallbackAuthorId): ?int
    {
        return ClubMembership::query()
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->value('user_id') ?? $fallbackAuthorId;
    }
}
