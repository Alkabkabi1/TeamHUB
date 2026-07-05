<?php

namespace App\Actions\Dashboard;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\DemoWalkthroughBootstrap;

class CreateProjectAction
{
    /**
     * @param  array{name: string, workspace_id?: int|null, leader_id?: int|null}  $data
     */
    public function execute(User $actor, Workspace $workspace, array $data): Project
    {
        $project = $workspace->projects()->create([
            'name' => $data['name'],
            'description' => null,
            'status' => 'active',
        ]);

        if (! empty($data['leader_id'])) {
            $leader = User::query()->findOrFail($data['leader_id']);
            app(AssignProjectLeaderAction::class)->execute($actor, $project, $leader);
        }

        DemoWalkthroughBootstrap::ensureDemoStaffOnProject($project, $actor);

        return $project;
    }
}
