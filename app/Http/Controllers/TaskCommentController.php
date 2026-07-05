<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCommentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskCommentController extends Controller
{
    public function store(
        StoreTaskCommentRequest $request,
        Workspace $workspace,
        Project $project,
        Task $task,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $task->addComment($user, $request->validated('body'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.comment_posted'),
        ]);

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Task $task,
        TaskComment $comment,
    ): RedirectResponse {
        /** @var User $user */
        $user = request()->user();

        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($comment->user_id === $user->id || $user->canManageProject($project), 403);

        $comment->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.comment_deleted'),
        ]);

        return redirect()->route('projects.tasks.show', [$workspace, $project, $task]);
    }
}
