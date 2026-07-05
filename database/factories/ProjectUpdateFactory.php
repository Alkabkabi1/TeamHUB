<?php

namespace Database\Factories;

use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\Support\DemoCoverImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectUpdate>
 */
class ProjectUpdateFactory extends Factory
{
    protected $model = ProjectUpdate::class;

    private const TITLES = [
        'الفريق يختتم مرحلة التخطيط بنجاح',
        'إطلاق البرنامج التدريبي الجديد',
        'تحديث حول تقدم المشروع',
    ];

    private const BODIES = [
        "شارك الفريق خلال الفترة الماضية في أنشطة تنسيق المهام والتسليمات.\n\nوتم الاتفاق على الخطوات القادمة وتوزيع المسؤوليات بين الأعضاء.",
        "أعلن الفريق عن فتح باب المشاركة في البرنامج التدريبي الجديد.\n\nالمقاعد محدودة، لذا يُنصح بالتسجيل المبكر.",
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'title' => fake()->randomElement(self::TITLES),
            'body' => fake()->randomElement(self::BODIES),
            'published_at' => now()->subDays(rand(1, 30)),
        ];
    }

    public function withImages(int $count = 1): static
    {
        return $this->afterCreating(function (ProjectUpdate $update) use ($count): void {
            for ($i = 0; $i < $count; $i++) {
                $bytes = DemoCoverImage::generate("update-{$update->id}-{$i}", $update->workspace?->theme);

                $update->addMediaFromString($bytes)
                    ->usingFileName("cover-{$i}.jpg")
                    ->toMediaCollection(ProjectUpdate::IMAGE_COLLECTION);
            }
        });
    }
}
