<?php

namespace Database\Factories;

use App\Models\CertificateTemplate;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateTemplate>
 */
class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => fake()->words(2, true).' certificate',
            'status' => 'draft',
            'is_default' => false,
            'width' => 1123,
            'height' => 794,
        ];
    }

    /**
     * Mark the template active (issuable).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    /**
     * Mark the template as the club's active default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
            'is_default' => true,
        ]);
    }

    /**
     * Attach a small but valid PNG background to the created template, so the
     * PDF renderer can embed it during tests.
     */
    public function withImage(): static
    {
        return $this->afterCreating(function (CertificateTemplate $template): void {
            $image = imagecreatetruecolor(8, 6);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

            ob_start();
            imagepng($image);
            $png = (string) ob_get_clean();
            imagedestroy($image);

            $template->addMediaFromString($png)
                ->usingFileName('template.png')
                ->toMediaCollection(CertificateTemplate::TEMPLATE_COLLECTION);
        });
    }
}
