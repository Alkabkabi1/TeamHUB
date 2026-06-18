<?php

namespace Database\Seeders;

use App\Enums\CertificateField;
use App\Models\CertificateTemplate;
use App\Models\Club;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CertificateTemplateSeeder extends Seeder
{
    /**
     * Give every club an active default certificate template with a small set
     * of placeholders, so certificates can be issued out of the box.
     */
    public function run(): void
    {
        Club::query()->each(function (Club $club): void {
            if ($club->certificateTemplates()->exists()) {
                return;
            }

            $template = $club->certificateTemplates()->create([
                'name' => 'شهادة مشاركة',
                'status' => 'active',
                'is_default' => true,
                'width' => 1123,
                'height' => 794,
            ]);

            $this->attachBackground($template);
            $this->seedPlaceholders($template);
        });
    }

    /**
     * Generate a simple A4-landscape background and attach it.
     */
    private function attachBackground(CertificateTemplate $template): void
    {
        $width = 1123;
        $height = 794;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $brand = imagecolorallocate($image, 0, 100, 113);

        imagefill($image, 0, 0, $white);
        imagesetthickness($image, 6);
        imagerectangle($image, 30, 30, $width - 30, $height - 30, $brand);

        $path = tempnam(sys_get_temp_dir(), 'cert').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        $template->addMedia($path)
            ->usingFileName('template.png')
            ->toMediaCollection(CertificateTemplate::TEMPLATE_COLLECTION);

        File::delete($path);
    }

    /**
     * Seed a representative set of bound placeholders.
     */
    private function seedPlaceholders(CertificateTemplate $template): void
    {
        $placeholders = [
            [CertificateField::ClubName, 0.2, 0.18, 0.6, 0.03, 'normal', '#006471'],
            [CertificateField::RecipientName, 0.15, 0.42, 0.7, 0.06, 'bold', '#111111'],
            [CertificateField::EventTitle, 0.2, 0.58, 0.6, 0.035, 'normal', '#333333'],
            [CertificateField::IssueDate, 0.1, 0.85, 0.3, 0.022, 'normal', '#555555'],
            [CertificateField::CertificateNumber, 0.6, 0.85, 0.3, 0.022, 'normal', '#555555'],
        ];

        foreach ($placeholders as $sort => [$field, $x, $y, $widthFraction, $fontSize, $weight, $color]) {
            $template->placeholders()->create([
                'binding' => $field->value,
                'x' => $x,
                'y' => $y,
                'width' => $widthFraction,
                'font_size' => $fontSize,
                'font_family' => 'DejaVu Sans',
                'font_weight' => $weight,
                'color' => $color,
                'align' => 'center',
                'sort' => $sort,
            ]);
        }
    }
}
