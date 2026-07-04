<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubResource;
use Database\Factories\Support\DemoCoverImage;
use Database\Factories\Support\DemoPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ClubResourcesSeeder extends Seeder
{
    /**
     * Curated pool of downloadable documents. Each club gets a rotating slice
     * of these (rather than the same two everywhere) so the resources catalog
     * reads as a varied library.
     *
     * @var list<array{title: string, description: string, access: string}>
     */
    private array $downloads = [
        ['title' => 'دليل الانضمام للأندية الطلابية', 'description' => 'ملف تعريفي شامل بخطوات التسجيل والانضمام للنادي.', 'access' => 'عام'],
        ['title' => 'سياسة احتساب الساعات التطوعية', 'description' => 'توضيح آلية احتساب واعتماد الساعات التطوعية للأعضاء.', 'access' => 'خاص'],
        ['title' => 'لائحة الأنشطة الطلابية', 'description' => 'الضوابط المنظمة لإقامة الأنشطة والفعاليات داخل الجامعة.', 'access' => 'عام'],
        ['title' => 'نموذج طلب إقامة فعالية', 'description' => 'النموذج الرسمي لتقديم طلب تنظيم فعالية جديدة.', 'access' => 'خاص'],
        ['title' => 'الخطة الفصلية لأنشطة النادي', 'description' => 'جدول الأنشطة والبرامج المعتمدة للفصل الدراسي الحالي.', 'access' => 'عام'],
        ['title' => 'دليل الهوية البصرية للنادي', 'description' => 'إرشادات استخدام الشعار والألوان في مواد النادي.', 'access' => 'خاص'],
    ];

    /**
     * Curated pool of media gallery items (images).
     *
     * @var list<array{title: string, description: string, access: string}>
     */
    private array $media = [
        ['title' => 'معرض صور يوم التطوع', 'description' => 'لقطات من فعالية التطوع الجامعية.', 'access' => 'عام'],
        ['title' => 'صور الحفل السنوي لتكريم الأعضاء', 'description' => 'أبرز اللحظات من حفل التكريم السنوي.', 'access' => 'عام'],
        ['title' => 'لقطات من معرض المشاريع الطلابية', 'description' => 'مشاريع الأعضاء المعروضة خلال المعرض.', 'access' => 'عام'],
        ['title' => 'ألبوم ورشة العمل التدريبية', 'description' => 'صور من جلسات الورشة التدريبية الأخيرة.', 'access' => 'عام'],
        ['title' => 'صور رحلة النادي الميدانية', 'description' => 'مشاهد من الرحلة الميدانية لأعضاء النادي.', 'access' => 'عام'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubs = Club::query()->where('status', 'active')->get();

        foreach ($clubs as $index => $club) {
            // Rotate the starting point per club so different clubs surface
            // different resources across the catalog.
            $this->seedDownloads($club, $index);
            $this->seedMedia($club, $index);

            // Tag each resource with its club's tags so the resources catalog
            // has tags to filter by out of the box.
            $tagIds = $club->tags()->pluck('tags.id');
            $club->resources()->each(fn (ClubResource $resource) => $resource->tags()->syncWithoutDetaching($tagIds));
        }
    }

    private function seedDownloads(Club $club, int $clubIndex): void
    {
        foreach ($this->slice($this->downloads, $clubIndex, 3) as $position => $item) {
            $path = "resources/club-{$club->id}-doc-{$position}.pdf";

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, DemoPdf::generate('TeamHUB - '.$club->name));
            }

            ClubResource::firstOrCreate(
                ['club_id' => $club->id, 'title' => $item['title'], 'type' => ClubResource::TYPE_DOWNLOAD],
                [
                    'description' => $item['description'],
                    'access' => $item['access'],
                    'format' => 'PDF',
                    'type' => ClubResource::TYPE_DOWNLOAD,
                    'file_path' => $path,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
                ]
            );
        }
    }

    private function seedMedia(Club $club, int $clubIndex): void
    {
        foreach ($this->slice($this->media, $clubIndex, 3) as $position => $item) {
            $path = "resources/club-{$club->id}-media-{$position}.jpg";

            if (! Storage::disk('public')->exists($path)) {
                $bytes = DemoCoverImage::generate("resource-{$club->id}-{$position}", $club->theme);
                Storage::disk('public')->put($path, $bytes);
            }

            ClubResource::firstOrCreate(
                ['club_id' => $club->id, 'title' => $item['title'], 'type' => ClubResource::TYPE_MEDIA],
                [
                    'description' => $item['description'],
                    'access' => $item['access'],
                    'format' => 'JPG',
                    'type' => ClubResource::TYPE_MEDIA,
                    'file_path' => $path,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
                ]
            );
        }
    }

    /**
     * Take $count items from $pool starting at an offset derived from the club
     * index, wrapping around so every club gets a distinct, full slice.
     *
     * @param  list<array{title: string, description: string, access: string}>  $pool
     * @return list<array{title: string, description: string, access: string}>
     */
    private function slice(array $pool, int $clubIndex, int $count): array
    {
        $size = count($pool);
        $start = ($clubIndex * 2) % $size;

        return array_map(fn (int $i): array => $pool[($start + $i) % $size], range(0, min($count, $size) - 1));
    }
}
