<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Tag;
use App\Models\University;
use Database\Factories\Support\DemoClubLogo;
use Illuminate\Database\Seeder;

class ClubsSeeder extends Seeder
{
    /**
     * Whether to attach club logos (the real CS logo and generated monogram
     * emblems). Disabled for now so clubs render with the clean placeholder,
     * ready for real uploads; flip to true to re-enable (the bundled logo,
     * DemoClubLogo and attachLogo are kept for that).
     */
    private const SEED_LOGOS = false;

    /**
     * A small, curated set of demo clubs.
     *
     * - نادي الحاسبات uses the real university CS-club logo, so the flagship
     *   club reads as a fully branded, production-like entry.
     * - Every other club gets a distinct brand color and a generated monogram
     *   emblem tinted to match, so per-club theming (color + logo) is easy to
     *   eyeball across the clubs grid.
     * - The last two cover the inactive / founding statuses.
     *
     * @var list<array{name: string, category: string, college: string, status: string, theme: string, logoFile?: string, monogram?: string, tags: list<string>}>
     */
    private array $clubs = [
        ['name' => 'نادي الحاسبات', 'category' => 'تقني', 'college' => 'كلية الحاسبات والمعلومات', 'status' => 'active', 'theme' => '#006471', 'logoFile' => 'images/clubs/computing.png', 'tags' => ['تقني', 'برمجة']],

        ['name' => 'نادي البيئة', 'category' => 'تطوعي', 'college' => 'كلية العلوم', 'status' => 'active', 'theme' => '#1B5E20', 'monogram' => 'ب', 'tags' => ['تطوعي', 'بيئة']],
        ['name' => 'نادي الفنون', 'category' => 'ثقافي', 'college' => 'كلية التربية', 'status' => 'active', 'theme' => '#6A1B9A', 'monogram' => 'ف', 'tags' => ['ثقافي', 'فنون']],
        ['name' => 'نادي الابتكار', 'category' => 'تقني', 'college' => 'كلية الهندسة', 'status' => 'active', 'theme' => '#E65100', 'monogram' => 'ك', 'tags' => ['تقني', 'ابتكار', 'ريادة']],

        ['name' => 'نادي التطوع', 'category' => 'تطوعي', 'college' => 'كلية الطب', 'status' => 'inactive', 'theme' => '#558B2F', 'monogram' => 'ط', 'tags' => ['تطوعي']],
        ['name' => 'نادي الروبوتات', 'category' => 'تقني', 'college' => 'كلية الهندسة', 'status' => 'founding', 'theme' => '#283593', 'monogram' => 'ر', 'tags' => ['تقني', 'روبوتات']],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universityId = University::query()->orderBy('id')->value('id');

        foreach ($this->clubs as $data) {
            $club = Club::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category' => $data['category'],
                    'college' => $data['college'],
                    'status' => $data['status'],
                    'theme' => $data['theme'],
                    'university_id' => $universityId,
                ]
            );

            if (self::SEED_LOGOS) {
                $this->attachLogo($club, $data['logoFile'] ?? null, $data['monogram'] ?? null);
            }

            $tagIds = collect($data['tags'])
                ->map(fn (string $name): int => Tag::firstOrCreate(['name' => $name])->id)
                ->all();

            $club->tags()->syncWithoutDetaching($tagIds);
        }
    }

    /**
     * Attach a logo to the club's single-file "logo" media collection.
     * Prefers a bundled real logo file when given; otherwise falls back to a
     * generated monogram emblem tinted with the club's theme color. Idempotent:
     * skips clubs that already have a logo.
     */
    private function attachLogo(Club $club, ?string $logoFile, ?string $monogram): void
    {
        if ($club->hasMedia(Club::LOGO_COLLECTION)) {
            return;
        }

        if ($logoFile !== null && is_file(public_path($logoFile))) {
            $club->addMedia(public_path($logoFile))
                ->preservingOriginal()
                ->usingFileName('logo.'.pathinfo($logoFile, PATHINFO_EXTENSION))
                ->toMediaCollection(Club::LOGO_COLLECTION);

            return;
        }

        if ($monogram === null) {
            return;
        }

        $club->addMediaFromString(DemoClubLogo::generate($monogram, $club->theme))
            ->usingFileName('logo.jpg')
            ->toMediaCollection(Club::LOGO_COLLECTION);
    }
}
