<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskDetailController extends Controller
{
    public function show(Task $task): RedirectResponse
    {
        $this->authorize('view', $task);

        $task->loadMissing('project:id,workspace_id');

        return redirect()->route('projects.tasks.show', [
            $task->project?->workspace_id,
            $task->project_id,
            $task,
        ]);
    }
}
