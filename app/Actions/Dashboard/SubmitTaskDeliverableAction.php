<?php

namespace App\Actions\Dashboard;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class SubmitTaskDeliverableAction
{
    /**
     * @param  array{deliverable_url?: string|null, deliverable_notes?: string|null}  $data
     */
    public function execute(User $actor, Task $task, array $data, ?UploadedFile $file = null): Task
    {
        $task->submitDeliverable(
            $actor,
            $data['deliverable_url'] ?? null,
            $data['deliverable_notes'] ?? null,
            $file !== null,
        );

        if ($file !== null) {
            $task->addMedia($file)->toMediaCollection(Task::DELIVERABLE_COLLECTION);
        }

        return $task;
    }
}
