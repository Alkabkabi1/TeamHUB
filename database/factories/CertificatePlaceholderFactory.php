<?php

namespace Database\Factories;

use App\Enums\CertificateField;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificatePlaceholder>
 */
class CertificatePlaceholderFactory extends Factory
{
    protected $model = CertificatePlaceholder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certificate_template_id' => CertificateTemplate::factory(),
            'binding' => CertificateField::RecipientName->value,
            'static_text' => null,
            'x' => fake()->randomFloat(5, 0.1, 0.7),
            'y' => fake()->randomFloat(5, 0.1, 0.8),
            'width' => 0.4,
            'font_size' => 0.04,
            'font_family' => 'DejaVu Sans',
            'font_weight' => 'normal',
            'color' => '#000000',
            'align' => 'center',
            'sort' => 0,
        ];
    }

    /**
     * Bind the placeholder to a specific field.
     */
    public function binding(CertificateField $field): static
    {
        return $this->state(fn (array $attributes): array => [
            'binding' => $field->value,
        ]);
    }
}
