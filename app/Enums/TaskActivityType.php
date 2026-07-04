<?php

namespace App\Enums;

enum TaskActivityType: string
{
    case TaskCreated = 'task.created';
    case TaskStatusChanged = 'task.status_changed';
    case TaskAssigned = 'task.assigned';
    case DeliverableSubmitted = 'task.deliverable_submitted';
    case DeliverableApproved = 'task.deliverable_approved';
    case ChangesRequested = 'task.changes_requested';
    case CommentAdded = 'comment.added';
}
