<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskReviewController extends Controller
{
    public function approve(Request $request, Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('approveDeliverable', $task);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->approve($user, $validated['review_notes'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.review_approved'),
        ]);

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }

    public function requestChanges(Request $request, Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('requestChanges', $task);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->requestChanges($user, $validated['review_notes'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.review_changes_requested'),
        ]);

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }
}
