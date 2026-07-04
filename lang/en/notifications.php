<?php

return [
    'title' => 'Notifications',
    'subtitle' => 'Track assignments, reviews, and task updates in one place.',
    'unread' => 'Unread',
    'read' => 'Earlier',
    'empty' => 'No notifications yet.',
    'mark_read' => 'Mark as read',
    'mark_all_read' => 'Mark all as read',
    'open' => 'Open',
    'task_assigned' => [
        'title' => 'New task assigned',
        'body' => ':actor assigned \":task\" in :project.',
        'action' => 'Open task',
        'mail_subject' => 'New task assigned: :task',
    ],
    'task_submitted_for_review' => [
        'title' => 'Task submitted for review',
        'body' => ':actor submitted deliverables for \":task\" in :project.',
        'action' => 'Review task',
        'mail_subject' => ':task is ready for review',
    ],
    'task_deliverable_approved' => [
        'title' => 'Deliverable approved',
        'body' => ':actor approved the deliverable for \":task\".',
        'action' => 'Open task',
        'mail_subject' => ':task was approved',
    ],
    'task_changes_requested' => [
        'title' => 'Changes requested',
        'body' => ':actor requested changes on \":task\".',
        'action' => 'Open task',
        'mail_subject' => 'Changes requested for :task',
    ],
];
