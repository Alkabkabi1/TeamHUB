<?php

namespace Database\Seeders;

use App\Models\Workspace;
use Database\Factories\Support\DemoClubLogo;
use Illuminate\Database\Seeder;

class WorkspacesSeeder extends Seeder
{
    private const SEED_LOGOS = false;

    /**
     * @var list<array{name: string, status: string, theme: string, logoFile?: string, monogram?: string}>
     */
    private array $workspaces = [
        ['name' => 'مساحة الحاسبات', 'status' => 'active', 'theme' => '#c8924a', 'logoFile' => 'images/clubs/computing.png'],
        ['name' => 'مساحة البيئة', 'status' => 'active', 'theme' => '#1B5E20', 'monogram' => 'ب'],
        ['name' => 'مساحة الفنون', 'status' => 'active', 'theme' => '#6A1B9A', 'monogram' => 'ف'],
        ['name' => 'مساحة الابتكار', 'status' => 'active', 'theme' => '#E65100', 'monogram' => 'ك'],
        ['name' => 'مساحة التطوع', 'status' => 'inactive', 'theme' => '#558B2F', 'monogram' => 'ط'],
        ['name' => 'مساحة الروبوتات', 'status' => 'founding', 'theme' => '#283593', 'monogram' => 'ر'],
    ];

    public function run(): void
    {
        foreach ($this->workspaces as $data) {
            $workspace = Workspace::firstOrCreate(
                ['name' => $data['name']],
                [
                    'status' => $data['status'],
                    'theme' => $data['theme'],
                ],
            );

            if (self::SEED_LOGOS && ! empty($data['monogram'])) {
                $workspace->addMediaFromString(DemoClubLogo::generate($data['monogram'], $workspace->theme ?? '#7c3aed'))
                    ->usingFileName('logo.jpg')
                    ->toMediaCollection(Workspace::LOGO_COLLECTION);
            }
        }
    }
}
