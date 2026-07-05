<?php

namespace App\Actions\Dashboard;

use App\Models\Task;
use App\Models\User;

class ReviewTaskDeliverableAction
{
    public function approve(User $reviewer, Task $task, ?string $notes = null): Task
    {
        $task->approve($reviewer, $notes);

        return $task;
    }

    public function requestChanges(User $reviewer, Task $task, ?string $notes = null): Task
    {
        $task->requestChanges($reviewer, $notes);

        return $task;
    }
}
