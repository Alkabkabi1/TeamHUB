<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitTaskDeliverableRequest;
use App\Models\Club;
use App\Models\Committee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskDeliverableController extends Controller
{
    public function store(
        SubmitTaskDeliverableRequest $request,
        Club $club,
        Committee $committee,
        Task $task,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $task->submitDeliverable(
            $user,
            $request->validated('deliverable_url'),
            $request->validated('deliverable_notes'),
            $request->hasFile('deliverable_file'),
        );

        if ($request->hasFile('deliverable_file')) {
            $task->addMedia($request->file('deliverable_file'))
                ->toMediaCollection(Task::DELIVERABLE_COLLECTION);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.deliverable_submitted'),
        ]);

        return redirect()->route('committees.tasks.show', [$club, $committee, $task]);
    }
}
