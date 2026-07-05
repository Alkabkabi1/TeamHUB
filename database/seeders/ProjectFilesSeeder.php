<?php

namespace Database\Seeders;

use App\Models\ProjectFile;
use App\Models\Workspace;
use Database\Factories\Support\DemoCoverImage;
use Database\Factories\Support\DemoPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProjectFilesSeeder extends Seeder
{
    /**
     * @var list<array{title: string, description: string, access: string}>
     */
    private array $downloads = [
        ['title' => 'دليل الانضمام للفريق', 'description' => 'ملف تعريفي بخطوات الانضمام.', 'access' => 'عام'],
        ['title' => 'سياسة إدارة المهام', 'description' => 'توضيح آلية تسليم المخرجات ومراجعتها.', 'access' => 'خاص'],
        ['title' => 'نموذج طلب مشروع جديد', 'description' => 'النموذج الرسمي لاقتراح مشروع.', 'access' => 'عام'],
    ];

    /**
     * @var list<array{title: string, description: string, access: string}>
     */
    private array $media = [
        ['title' => 'معرض صور الفعالية', 'description' => 'لقطات من آخر فعالية للفريق.', 'access' => 'عام'],
        ['title' => 'لقطات من ورشة العمل', 'description' => 'صور من جلسات الورشة التدريبية.', 'access' => 'عام'],
    ];

    public function run(): void
    {
        $workspaces = Workspace::query()->where('status', 'active')->get();

        foreach ($workspaces as $index => $workspace) {
            $this->seedDownloads($workspace, $index);
            $this->seedMedia($workspace, $index);
        }
    }

    private function seedDownloads(Workspace $workspace, int $index): void
    {
        foreach ($this->slice($this->downloads, $index, 2) as $position => $item) {
            $path = "resources/workspace-{$workspace->id}-doc-{$position}.pdf";

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, DemoPdf::generate('TeamHUB - '.$workspace->name));
            }

            ProjectFile::firstOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $item['title'], 'type' => ProjectFile::TYPE_DOWNLOAD],
                [
                    'description' => $item['description'],
                    'access' => $item['access'],
                    'format' => 'PDF',
                    'type' => ProjectFile::TYPE_DOWNLOAD,
                    'file_path' => $path,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
                ],
            );
        }
    }

    private function seedMedia(Workspace $workspace, int $index): void
    {
        foreach ($this->slice($this->media, $index, 2) as $position => $item) {
            $path = "resources/workspace-{$workspace->id}-media-{$position}.jpg";

            if (! Storage::disk('public')->exists($path)) {
                $bytes = DemoCoverImage::generate("resource-{$workspace->id}-{$position}", $workspace->theme);
                Storage::disk('public')->put($path, $bytes);
            }

            ProjectFile::firstOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $item['title'], 'type' => ProjectFile::TYPE_MEDIA],
                [
                    'description' => $item['description'],
                    'access' => $item['access'],
                    'format' => 'JPG',
                    'type' => ProjectFile::TYPE_MEDIA,
                    'file_path' => $path,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
                ],
            );
        }
    }

    /**
     * @param  list<array{title: string, description: string, access: string}>  $pool
     * @return list<array{title: string, description: string, access: string}>
     */
    private function slice(array $pool, int $index, int $count): array
    {
        $size = count($pool);
        $start = ($index * 2) % $size;

        return array_map(fn (int $i): array => $pool[($start + $i) % $size], range(0, min($count, $size) - 1));
    }
}
