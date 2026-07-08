<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$workspace = Workspace::query()->where('name', 'مساحة الحاسبات')->first();
$project = Project::query()
    ->where('workspace_id', $workspace?->id)
    ->where('name', 'المبادرة العلمية')
    ->first();
$lead = User::query()->where('email', 'project-lead@teamhub.test')->first();
$member = User::query()->where('email', 'student@teamhub.test')->first();

if ($workspace === null || $project === null || $lead === null || $member === null) {
    fwrite(STDERR, "Screenshot seed prerequisites missing. Run migrate:fresh --seed first.\n");
    exit(1);
}

Task::query()->where('project_id', $project->id)->delete();

$tasks = [
    [
        'title' => 'إعداد خطة المبادرة العلمية',
        'description' => 'صياغة الأهداف والجدول الزمني للمبادرة.',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'assigned_to' => $member->id,
        'due_at' => now()->addDays(2),
    ],
    [
        'title' => 'مراجعة المحتوى التقني',
        'description' => 'مراجعة المسودة قبل النشر.',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::Medium,
        'assigned_to' => $member->id,
        'due_at' => now()->addDay(),
        'submit_deliverable' => true,
    ],
    [
        'title' => 'تصميم الهوية البصرية',
        'description' => 'تسليم ملفات الشعار والألوان.',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::Low,
        'assigned_to' => $member->id,
        'due_at' => now()->subDays(2),
        'approve' => true,
    ],
];

$created = [];

foreach ($tasks as $data) {
    $submitDeliverable = (bool) ($data['submit_deliverable'] ?? false);
    $approve = (bool) ($data['approve'] ?? false);
    unset($data['submit_deliverable'], $data['approve']);

    $task = Task::create([
        ...$data,
        'project_id' => $project->id,
        'created_by' => $lead->id,
    ]);

    $task->recordCreated($lead);

    if ($task->assigned_to !== null) {
        $task->recordAssignment($lead, null, $member);
    }

    if ($submitDeliverable) {
        $task->submitDeliverable(
            $member,
            'https://example.com/draft',
            'المسودة جاهزة للمراجعة.',
        );
        $task->addComment($member, 'المسودة جاهزة للمراجعة.');
    }

    if ($approve) {
        $task->submitDeliverable(
            $member,
            'https://example.com/brand-kit',
            'ملفات الهوية البصرية.',
        );
        $task->approve($lead, 'تمت الموافقة على التسليم.');
    }

    $created[] = [
        'id' => $task->id,
        'title' => $task->title,
        'status' => $task->fresh()->status->value,
    ];
}

$paths = [
    'workspace_id' => $workspace->id,
    'project_id' => $project->id,
    'review_task_id' => $created[1]['id'] ?? null,
    'paths' => [
        'entry' => '/',
        'my_tasks' => '/my-tasks',
        'workspace' => "/workspaces/{$workspace->id}",
        'project' => "/workspaces/{$workspace->id}/projects/{$project->id}",
        'task_list' => "/workspaces/{$workspace->id}/projects/{$project->id}/tasks",
        'task_detail' => isset($created[1]['id'])
            ? "/workspaces/{$workspace->id}/projects/{$project->id}/tasks/{$created[1]['id']}"
            : null,
    ],
];

file_put_contents(
    __DIR__.'/../docs/screenshots/paths.json',
    json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL,
);

echo json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
