<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Event;
use Database\Factories\EventFactory;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    /**
     * Whether to attach generated cover images to seeded events. Disabled for
     * now so events render with the clean placeholder, ready for real uploads;
     * flip to true to re-enable the generated covers (EventFactory::withImages
     * and DemoCoverImage are kept for that).
     */
    private const SEED_IMAGES = false;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csClub = Club::query()->where('name', 'نادي الحاسبات')->first();

        // Idempotent: skip clubs that already have events so re-seeding does not
        // keep appending new events (which would cascade into more attendances).
        if ($csClub && ! $csClub->events()->exists()) {
            $this->maybeImages(Event::factory()->upcoming())->count(4)->for($csClub)->create();
            // Past events get small galleries so event detail pages showcase the
            // multi-image gallery, not just a single cover (when images are on).
            $this->maybeImages(Event::factory()->past(), 3)->count(3)->for($csClub)->create();
            $this->maybeImages(Event::factory())->count(1)->for($csClub)->create(['status' => 'draft']);
            $this->maybeImages(Event::factory())->count(1)->for($csClub)->create(['status' => 'cancelled']);

            $this->inheritClubTags($csClub);
        }

        Club::query()->where('status', 'active')->where('name', '!=', 'نادي الحاسبات')->each(function (Club $club): void {
            if ($club->events()->exists()) {
                return;
            }

            $this->maybeImages(Event::factory()->upcoming())->count(fake()->numberBetween(1, 2))->for($club)->create();
            $this->maybeImages(Event::factory()->past(), 2)->count(fake()->numberBetween(1, 2))->for($club)->create();

            $this->inheritClubTags($club);
        });
    }

    /**
     * Apply generated cover images to the factory only when image seeding is
     * enabled, otherwise return it unchanged.
     */
    private function maybeImages(EventFactory $factory, int $count = 1): EventFactory
    {
        return self::SEED_IMAGES ? $factory->withImages($count) : $factory;
    }

    /**
     * Tag a club's events with the club's own tags so the events catalog has
     * tags to filter by out of the box.
     */
    private function inheritClubTags(Club $club): void
    {
        $tagIds = $club->tags()->pluck('tags.id');

        $club->events()->each(fn (Event $event) => $event->tags()->syncWithoutDetaching($tagIds));
    }
}
