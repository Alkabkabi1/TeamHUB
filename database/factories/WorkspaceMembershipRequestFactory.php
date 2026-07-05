<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceMembershipRequest>
 */
class WorkspaceMembershipRequestFactory extends Factory
{
    protected $model = WorkspaceMembershipRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'phone' => '05'.fake()->numerify('########'),
            'skills' => fake()->randomElement([
                'التصميم الجرافيكي وإدارة وسائل التواصل',
                'البرمجة وتطوير تطبيقات الويب',
                'تنظيم الفعاليات والعمل الجماعي',
            ]),
            'weekly_hours' => fake()->numberBetween(2, 10),
            'tools' => 'Figma, Office',
            'motivation' => 'أرغب في الانضمام للفريق وتطوير مهاراتي والمساهمة في المشاريع.',
            'contribution' => 'أستطيع المساهمة في تنظيم المهام وإعداد المحتوى والعمل الجماعي.',
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
            'reviewed_by' => User::factory()->admin(),
        ]);
    }
}
