<?php

namespace App\Concerns;

use App\Enums\ProjectCapability;
use App\Enums\WorkspaceCapability;
use App\Models\Project;

trait AuthorizesWorkspaceOrProject
{
    protected function authorizeWorkspaceOrProject(WorkspaceCapability $workspaceCapability, ProjectCapability $projectCapability): bool
    {
        $project = $this->route('project') ?? $this->route('project');

        if ($project instanceof Project) {
            return $this->user()?->can($projectCapability->value, $project) ?? false;
        }

        return $this->user()?->can($workspaceCapability->value, $this->route('workspace') ?? $this->route('workspace')) ?? false;
    }
}
