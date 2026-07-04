<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubJoinApplication>
 */
class ClubJoinApplicationFactory extends Factory
{
    protected $model = ClubJoinApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'university_email' => fake()->unique()->userName().'@teamhub.test',
            'phone' => '05'.fake()->numerify('########'),
            'level' => 'المستوى '.fake()->numberBetween(1, 10),
            'major' => fake()->randomElement(['هندسة البرمجيات', 'علوم الحاسب', 'نظم المعلومات']),
            'skills' => fake()->randomElement([
                'التصميم الجرافيكي وإدارة وسائل التواصل الاجتماعي',
                'البرمجة وتطوير تطبيقات الويب',
                'تنظيم الفعاليات والعمل الجماعي',
                'الكتابة والتحرير وإعداد المحتوى',
            ]),
            'weekly_hours' => fake()->numberBetween(2, 10),
            'tools' => 'Photoshop, Office',
            'motivation' => 'أرغب في الانضمام للنادي لتطوير مهاراتي والمساهمة في أنشطته، والاستفادة من الخبرات المتاحة في بيئة تشجع على الإبداع والعمل الجماعي.',
            'contribution' => 'أستطيع المساهمة في تنظيم الفعاليات وإعداد المحتوى والمشاركة الفاعلة في المبادرات التطوعية التي يقدمها النادي لخدمة الطلاب والمجتمع.',
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->clubSupervisor(),
        ]);
    }
}
