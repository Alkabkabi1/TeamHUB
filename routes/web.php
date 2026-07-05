<?php

use App\Http\Controllers\AssistantConfirmController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AssistantSuggestionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardActionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoEntryController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectManagementController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectMembershipController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectsOverviewController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDeliverableController;
use App\Http\Controllers\TaskDetailController;
use App\Http\Controllers\TaskReviewController;
use App\Http\Controllers\TasksOverviewController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceManagementController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceMembershipRequestController;
use App\Http\Controllers\WorkspaceReportController;
use App\Http\Controllers\WorkspaceThemeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/', function (Request $request) {
    if (config('demo.quick_login')) {
        return app(DemoEntryController::class)($request);
    }

    return app(HomeController::class)->index($request);
})->name('home');

Route::post('assistant/chat', AssistantController::class)
    ->middleware('throttle:20,1')
    ->name('assistant.chat');

Route::get('assistant/suggestions', AssistantSuggestionController::class)
    ->name('assistant.suggestions');

Route::post('assistant/confirm/{actionId}', AssistantConfirmController::class)
    ->middleware(['auth', 'throttle:20,1'])
    ->name('assistant.confirm');

Route::inertia('support', 'Support')
    ->name('support');

Route::post('support/contact', [ContactController::class, 'store'])->name('support.contact');

Route::post('demo-login', DemoLoginController::class)
    ->name('demo.login');

Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])
    ->name('workspaces.show');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('projects', [ProjectsOverviewController::class, 'index'])->name('projects');
    Route::get('tasks', [TasksOverviewController::class, 'index'])->name('tasks');
    Route::get('tasks/{task}', [TaskDetailController::class, 'show'])->name('tasks.show');

    Route::post('dashboard/projects', [DashboardActionController::class, 'storeProject'])
        ->name('dashboard.projects.store');
    Route::post('dashboard/assign-leader', [DashboardActionController::class, 'assignLeader'])
        ->name('dashboard.assign-leader');
    Route::post('dashboard/message-leader', [DashboardActionController::class, 'messageLeader'])
        ->name('dashboard.message-leader');
    Route::post('dashboard/tasks', [DashboardActionController::class, 'storeTask'])
        ->name('dashboard.tasks.store');
    Route::post('tasks/{task}/approve', [DashboardActionController::class, 'approveDeliverable'])
        ->name('tasks.approve');
    Route::post('tasks/{task}/request-changes', [DashboardActionController::class, 'requestChanges'])
        ->name('tasks.request-changes');
    Route::post('tasks/{task}/deliverable', [DashboardActionController::class, 'submitDeliverable'])
        ->name('tasks.deliverable');

    Route::get('student-dashboard', [StudentDashboardController::class, 'index'])
        ->name('student-dashboard');
    Route::get('my-tasks', [MyTasksController::class, 'index'])
        ->name('my-tasks');

    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::get('workspaces/{workspace}/theme/edit', [WorkspaceThemeController::class, 'edit'])
        ->name('workspaces.theme.edit');
    Route::put('workspaces/{workspace}/theme', [WorkspaceThemeController::class, 'update'])
        ->name('workspaces.theme.update');

    Route::get('workspaces/{workspace}/join', [WorkspaceMembershipRequestController::class, 'create'])
        ->name('workspaces.join.create');
    Route::post('workspaces/{workspace}/join', [WorkspaceMembershipRequestController::class, 'store'])
        ->name('workspaces.join.store');

    Route::post('workspaces/membership-requests/{application}/approve', [WorkspaceMembershipRequestController::class, 'approve'])
        ->name('workspaces.membership-requests.approve');
    Route::post('workspaces/membership-requests/{application}/reject', [WorkspaceMembershipRequestController::class, 'reject'])
        ->name('workspaces.membership-requests.reject');

    Route::get('workspaces/{workspace}/manage', [WorkspaceManagementController::class, 'index'])
        ->name('workspaces.manage');
    Route::get('workspaces/{workspace}/manage/members', [WorkspaceManagementController::class, 'members'])
        ->name('workspaces.manage.members');

    Route::get('workspaces/{workspace}/members/search', [WorkspaceMemberController::class, 'search'])
        ->name('workspaces.members.search');
    Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])
        ->name('workspaces.members.store');
    Route::put('workspaces/{workspace}/members/{membership}/roles', [WorkspaceMemberController::class, 'updateRoles'])
        ->name('workspaces.members.roles');
    Route::delete('workspaces/{workspace}/members/{membership}', [WorkspaceMemberController::class, 'destroy'])
        ->name('workspaces.members.destroy');

    Route::get('workspaces/{workspace}/reports/members', [WorkspaceReportController::class, 'members'])
        ->name('workspaces.reports.members');

    Route::scopeBindings()->group(function (): void {
        Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])
            ->name('projects.index');
        Route::get('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'show'])
            ->whereNumber('project')
            ->name('projects.show');

        Route::get('workspaces/{workspace}/projects/create', [ProjectController::class, 'create'])
            ->name('projects.create');
        Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])
            ->name('projects.store');
        Route::get('workspaces/{workspace}/projects/{project}/edit', [ProjectController::class, 'edit'])
            ->name('projects.edit');
        Route::put('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'update'])
            ->name('projects.update');
        Route::delete('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'destroy'])
            ->name('projects.destroy');

        Route::get('workspaces/{workspace}/projects/{project}/manage', [ProjectManagementController::class, 'index'])
            ->name('projects.manage');
        Route::get('workspaces/{workspace}/projects/{project}/files', [ProjectManagementController::class, 'files'])
            ->name('projects.files.index');
        Route::post('workspaces/{workspace}/projects/{project}/files', [ProjectFileController::class, 'store'])
            ->name('projects.files.store');
        Route::delete('workspaces/{workspace}/projects/{project}/files/{resource}', [ProjectFileController::class, 'destroy'])
            ->name('projects.files.destroy');
        Route::get('workspaces/{workspace}/projects/{project}/updates', [ProjectManagementController::class, 'updates'])
            ->name('projects.updates.index');

        Route::get('workspaces/{workspace}/projects/{project}/tasks', [TaskController::class, 'index'])
            ->name('projects.tasks.index');
        Route::post('workspaces/{workspace}/projects/{project}/tasks', [TaskController::class, 'store'])
            ->name('projects.tasks.store');
        Route::get('workspaces/{workspace}/projects/{project}/tasks/{task}', [TaskController::class, 'show'])
            ->name('projects.tasks.show');
        Route::patch('workspaces/{workspace}/projects/{project}/tasks/{task}', [TaskController::class, 'update'])
            ->name('projects.tasks.update');
        Route::delete('workspaces/{workspace}/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');
        Route::post('workspaces/{workspace}/projects/{project}/tasks/{task}/deliverable', [TaskDeliverableController::class, 'store'])
            ->name('projects.tasks.deliverable');
        Route::post('workspaces/{workspace}/projects/{project}/tasks/{task}/approve', [TaskReviewController::class, 'approve'])
            ->name('projects.tasks.approve');
        Route::post('workspaces/{workspace}/projects/{project}/tasks/{task}/request-changes', [TaskReviewController::class, 'requestChanges'])
            ->name('projects.tasks.request-changes');
        Route::post('workspaces/{workspace}/projects/{project}/tasks/{task}/comments', [TaskCommentController::class, 'store'])
            ->name('projects.tasks.comments.store');
        Route::delete('workspaces/{workspace}/projects/{project}/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
            ->name('projects.tasks.comments.destroy');

        Route::get('workspaces/{workspace}/projects/{project}/members/search', [ProjectMemberController::class, 'search'])
            ->name('projects.members.search');
        Route::post('workspaces/{workspace}/projects/{project}/members', [ProjectMemberController::class, 'store'])
            ->name('projects.members.store');
        Route::put('workspaces/{workspace}/projects/{project}/members/{membership}/roles', [ProjectMemberController::class, 'updateRoles'])
            ->name('projects.members.roles');
        Route::delete('workspaces/{workspace}/projects/{project}/members/{membership}', [ProjectMemberController::class, 'destroy'])
            ->name('projects.members.destroy');

        Route::post('workspaces/{workspace}/projects/{project}/join', [ProjectMembershipController::class, 'store'])
            ->name('projects.join');
        Route::post('workspaces/{workspace}/projects/{project}/memberships/{membership}/approve', [ProjectMembershipController::class, 'approve'])
            ->name('projects.memberships.approve');
        Route::post('workspaces/{workspace}/projects/{project}/memberships/{membership}/reject', [ProjectMembershipController::class, 'reject'])
            ->name('projects.memberships.reject');

        Route::get('workspaces/{workspace}/projects/{project}/updates/create', [ProjectUpdateController::class, 'create'])
            ->name('projects.updates.create');
        Route::post('workspaces/{workspace}/projects/{project}/updates', [ProjectUpdateController::class, 'store'])
            ->name('projects.updates.store');
        Route::get('workspaces/{workspace}/projects/{project}/updates/{post}/edit', [ProjectUpdateController::class, 'edit'])
            ->name('projects.updates.edit');
        Route::put('workspaces/{workspace}/projects/{project}/updates/{post}', [ProjectUpdateController::class, 'update'])
            ->name('projects.updates.update');
        Route::delete('workspaces/{workspace}/projects/{project}/updates/{post}', [ProjectUpdateController::class, 'destroy'])
            ->name('projects.updates.destroy');

        Route::get('workspaces/{workspace}/projects/{project}/reports/members', [ProjectReportController::class, 'members'])
            ->name('projects.reports.members');
    });
});

require __DIR__.'/settings.php';
