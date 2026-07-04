<?php

return [
    'title' => 'TeamHUB Assistant',
    'subtitle' => 'Ask about your workspaces, projects, tasks, blockers, and next steps.',
    'open' => 'Open TeamHUB Assistant',
    'close' => 'Close assistant',
    'placeholder' => 'Type your message…',
    'send' => 'Send',
    'greeting' => 'Hi! How can I help you today?',
    'greeting_hint' => 'I can summarize your tasks, explain what is blocked in a project, and prepare task updates for confirmation.',
    'thinking' => 'Thinking…',
    'reasoning' => 'Reasoning',
    'error' => 'Could not get a reply. Please try again.',
    'you' => 'You',
    'assistant' => 'Assistant',
    'new_chat' => 'New chat',
    'suggestions_title' => 'Try asking',
    'confirm' => 'Confirm',
    'cancel' => 'Cancel',
    'confirmation_cancelled' => 'The proposed action was cancelled.',
    'confirmation_cancelled_message' => 'The proposed action was cancelled.',
    'confirmation_success_prefix' => 'Action completed',
    'confirmation_failure_prefix' => 'Action failed',
    'confirmation_connection_error' => 'The action could not be completed because of a connection error.',

    // Tool activity labels surfaced live while the assistant works. Keys are
    // tool class basenames; `default` covers any tool without its own line.
    'activity' => [
        'default' => 'Working…',
        'GetAppRoutes' => 'Finding the right TeamHUB page…',
        'ListMyTasks' => 'Reviewing your tasks…',
        'FindTasks' => 'Searching project tasks…',
        'GetProjectSummary' => 'Summarizing the project…',
        'CreateTask' => 'Preparing the new task…',
        'AssignTask' => 'Preparing the task assignment…',
        'UpdateTaskStatus' => 'Preparing the task status update…',
        'UpdateTaskDetails' => 'Preparing the task detail changes…',
    ],

    // Starter prompts for the empty state. Sent verbatim when clicked, so they
    // read as first-person questions/requests.
    'suggestions' => [
        'login_help' => 'How do I sign in to TeamHUB?',
        'find_my_tasks_page' => 'Where can I find my tasks after I log in?',
        'teamhub_pages' => 'Show me the main TeamHUB pages.',
        'teamhub_capabilities' => 'What can the TeamHUB assistant help me do?',
        'my_overdue_tasks' => 'What tasks are overdue?',
        'my_tasks_today' => 'What tasks are due today?',
        'my_open_tasks' => 'Summarize my open tasks.',
        'my_upcoming_tasks' => 'What tasks are coming up next?',
        'project_blockers' => 'What is blocked in :project?',
        'project_summary' => 'Give me a summary of :project.',
        'create_task_due_friday' => 'Create a task due Friday.',
        'assign_task_example' => 'Assign this task to Ahmed.',
        'move_task_to_review' => 'Move this task to review.',
    ],
];
