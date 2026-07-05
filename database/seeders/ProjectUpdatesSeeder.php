<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Database\Factories\ProjectUpdateFactory;
use Illuminate\Database\Seeder;

class ProjectUpdatesSeeder extends Seeder
{
    private const SEED_IMAGES = false;

    public function run(): void
    {
        $workspaces = Workspace::query()->where('status', 'active')->get();

        if ($workspaces->isEmpty()) {
            return;
        }

        $fallbackAuthorId = WorkspaceMembership::query()
            ->where('status', 'approved')
            ->value('user_id');

        foreach ($workspaces as $workspace) {
            if ($workspace->updates()->exists()) {
                continue;
            }

            $authorId = WorkspaceMembership::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'approved')
                ->value('user_id') ?? $fallbackAuthorId;

            if ($authorId === null) {
                continue;
            }

            $factory = self::SEED_IMAGES ? ProjectUpdateFactory::new()->withImages() : ProjectUpdateFactory::new();

            $factory
                ->count(fake()->numberBetween(2, 3))
                ->create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $authorId,
                ]);
        }
    }
}
