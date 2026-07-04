<?php

use App\Http\Controllers\AssistantConfirmController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AssistantSuggestionController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubJoinApplicationController;
use App\Http\Controllers\ClubManagementController;
use App\Http\Controllers\ClubMemberController;
use App\Http\Controllers\ClubReportController;
use App\Http\Controllers\ClubThemeController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\CommitteeManagementController;
use App\Http\Controllers\CommitteeMemberController;
use App\Http\Controllers\CommitteeMembershipController;
use App\Http\Controllers\CommitteeReportController;
use App\Http\Controllers\CommitteeResourceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDeliverableController;
use App\Http\Controllers\TaskReviewController;
use App\Http\Controllers\TeamHub\TeamHubDashboardController;
use App\Http\Controllers\TeamHub\TeamHubDemoController;
use App\Http\Controllers\TeamHub\TeamHubProjectsController;
use App\Http\Controllers\TeamHub\TeamHubTasksController;
use App\Http\Controllers\TeamHubEntryController;
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
        return app(TeamHubEntryController::class)($request);
    }

    return app(HomeController::class)->index($request);
})->name('home');

Route::get('clubs', [PublicCatalogController::class, 'clubs'])
    ->name('clubs');

Route::get('clubs/{club}', [ClubController::class, 'show'])
    ->name('clubs.show');

Route::get('clubs/{club}/committees', [CommitteeController::class, 'index'])
    ->name('committees.index');

Route::get('clubs/{club}/committees/{committee}', [CommitteeController::class, 'show'])
    ->scopeBindings()
    ->whereNumber('committee')
    ->name('committees.show');

Route::get('resources', [PublicCatalogController::class, 'resources'])
    ->name('resources');

Route::get('search', SearchController::class)->name('search');

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

Route::prefix('preview/team-hub')->group(function () {
    Route::redirect('/', '/hub/dashboard');
    Route::redirect('/dashboard', '/hub/dashboard');
    Route::redirect('/tasks', '/hub/tasks');
    Route::redirect('/projects', '/hub/projects');
    Route::redirect('/deliverable', '/hub/tasks');
});

Route::post('support/contact', [ContactController::class, 'store'])->name('support.contact');

Route::post('demo-login', DemoLoginController::class)
    ->name('demo.login');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('join-applications/{application}/approve', [ClubJoinApplicationController::class, 'approve'])
        ->name('join-applications.approve');

    Route::post('join-applications/{application}/reject', [ClubJoinApplicationController::class, 'reject'])
        ->name('join-applications.reject');

    Route::get('clubs/{club}/theme/edit', [ClubThemeController::class, 'edit'])->name('clubs.theme.edit');
    Route::put('clubs/{club}/theme', [ClubThemeController::class, 'update'])->name('clubs.theme.update');

    Route::get('clubs/{club}/join', [ClubJoinApplicationController::class, 'create'])
        ->name('clubs.join.create');

    Route::post('clubs/{club}/join', [ClubJoinApplicationController::class, 'store'])
        ->name('clubs.join.store');

    Route::get('dashboard', function () {
        return redirect(auth()->user()->homeUrl());
    })->name('dashboard');

    Route::get('student-dashboard', [StudentDashboardController::class, 'index'])
        ->name('student-dashboard');
    Route::get('my-tasks', [MyTasksController::class, 'index'])
        ->name('my-tasks');

    Route::prefix('hub')->name('hub.')->group(function () {
        Route::redirect('/', '/hub/dashboard');
        Route::get('dashboard', [TeamHubDashboardController::class, 'index'])->name('dashboard');
        Route::get('projects', [TeamHubProjectsController::class, 'index'])->name('projects');
        Route::get('tasks', [TeamHubTasksController::class, 'index'])->name('tasks');
        Route::post('admin/projects', [TeamHubDemoController::class, 'storeProject'])->name('admin.projects.store');
        Route::post('admin/assign-leader', [TeamHubDemoController::class, 'assignLeader'])->name('admin.assign-leader');
        Route::post('admin/message-leader', [TeamHubDemoController::class, 'messageLeader'])->name('admin.message-leader');
        Route::post('leader/tasks', [TeamHubDemoController::class, 'storeTask'])->name('leader.tasks.store');
        Route::post('staff/deliverables/{task}', [TeamHubDemoController::class, 'submitDeliverable'])->name('staff.deliverable');
    });

    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::get('clubs/{club}/manage', [ClubManagementController::class, 'index'])
        ->name('clubs.manage');
    Route::get('clubs/{club}/manage/members', [ClubManagementController::class, 'members'])
        ->name('clubs.manage.members');

    Route::get('clubs/{club}/members/search', [ClubMemberController::class, 'search'])
        ->name('clubs.members.search');
    Route::post('clubs/{club}/members', [ClubMemberController::class, 'store'])
        ->name('clubs.members.store');
    Route::put('clubs/{club}/members/{membership}/roles', [ClubMemberController::class, 'updateRoles'])
        ->name('clubs.members.roles');
    Route::delete('clubs/{club}/members/{membership}', [ClubMemberController::class, 'destroy'])
        ->name('clubs.members.destroy');

    Route::get('clubs/{club}/reports/members', [ClubReportController::class, 'members'])
        ->name('clubs.reports.members');

    Route::delete('news/{post}', [NewsController::class, 'destroy'])->name('news.destroy');

    Route::scopeBindings()->group(function () {
        Route::get('clubs/{club}/committees/create', [CommitteeController::class, 'create'])
            ->name('committees.create');
        Route::post('clubs/{club}/committees', [CommitteeController::class, 'store'])
            ->name('committees.store');
        Route::get('clubs/{club}/committees/{committee}/edit', [CommitteeController::class, 'edit'])
            ->name('committees.edit');
        Route::put('clubs/{club}/committees/{committee}', [CommitteeController::class, 'update'])
            ->name('committees.update');
        Route::delete('clubs/{club}/committees/{committee}', [CommitteeController::class, 'destroy'])
            ->name('committees.destroy');

        Route::get('clubs/{club}/committees/{committee}/manage', [CommitteeManagementController::class, 'index'])
            ->name('committees.manage');
        Route::get('clubs/{club}/committees/{committee}/files', [CommitteeManagementController::class, 'files'])
            ->name('committees.files.index');
        Route::post('clubs/{club}/committees/{committee}/files', [CommitteeResourceController::class, 'store'])
            ->name('committees.files.store');
        Route::delete('clubs/{club}/committees/{committee}/files/{resource}', [CommitteeResourceController::class, 'destroy'])
            ->name('committees.files.destroy');
        Route::get('clubs/{club}/committees/{committee}/updates', [CommitteeManagementController::class, 'updates'])
            ->name('committees.updates.index');

        Route::get('clubs/{club}/committees/{committee}/tasks', [TaskController::class, 'index'])
            ->name('committees.tasks.index');
        Route::post('clubs/{club}/committees/{committee}/tasks', [TaskController::class, 'store'])
            ->name('committees.tasks.store');
        Route::get('clubs/{club}/committees/{committee}/tasks/{task}', [TaskController::class, 'show'])
            ->name('committees.tasks.show');
        Route::patch('clubs/{club}/committees/{committee}/tasks/{task}', [TaskController::class, 'update'])
            ->name('committees.tasks.update');
        Route::delete('clubs/{club}/committees/{committee}/tasks/{task}', [TaskController::class, 'destroy'])
            ->name('committees.tasks.destroy');
        Route::post('clubs/{club}/committees/{committee}/tasks/{task}/deliverable', [TaskDeliverableController::class, 'store'])
            ->name('committees.tasks.deliverable');
        Route::post('clubs/{club}/committees/{committee}/tasks/{task}/approve', [TaskReviewController::class, 'approve'])
            ->name('committees.tasks.approve');
        Route::post('clubs/{club}/committees/{committee}/tasks/{task}/request-changes', [TaskReviewController::class, 'requestChanges'])
            ->name('committees.tasks.request-changes');
        Route::post('clubs/{club}/committees/{committee}/tasks/{task}/comments', [TaskCommentController::class, 'store'])
            ->name('committees.tasks.comments.store');
        Route::delete('clubs/{club}/committees/{committee}/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
            ->name('committees.tasks.comments.destroy');

        Route::get('clubs/{club}/committees/{committee}/members/search', [CommitteeMemberController::class, 'search'])
            ->name('committees.members.search');
        Route::post('clubs/{club}/committees/{committee}/members', [CommitteeMemberController::class, 'store'])
            ->name('committees.members.store');
        Route::put('clubs/{club}/committees/{committee}/members/{membership}/roles', [CommitteeMemberController::class, 'updateRoles'])
            ->name('committees.members.roles');
        Route::delete('clubs/{club}/committees/{committee}/members/{membership}', [CommitteeMemberController::class, 'destroy'])
            ->name('committees.members.destroy');

        Route::post('clubs/{club}/committees/{committee}/join', [CommitteeMembershipController::class, 'store'])
            ->name('committees.join');
        Route::post('clubs/{club}/committees/{committee}/memberships/{membership}/approve', [CommitteeMembershipController::class, 'approve'])
            ->name('committees.memberships.approve');
        Route::post('clubs/{club}/committees/{committee}/memberships/{membership}/reject', [CommitteeMembershipController::class, 'reject'])
            ->name('committees.memberships.reject');

        Route::get('clubs/{club}/committees/{committee}/news/create', [NewsController::class, 'create'])
            ->name('committees.news.create');
        Route::post('clubs/{club}/committees/{committee}/news', [NewsController::class, 'store'])
            ->name('committees.news.store');
        Route::get('clubs/{club}/committees/{committee}/news/{post}/edit', [NewsController::class, 'edit'])
            ->name('committees.news.edit');
        Route::put('clubs/{club}/committees/{committee}/news/{post}', [NewsController::class, 'update'])
            ->name('committees.news.update');

        Route::get('clubs/{club}/committees/{committee}/reports/members', [CommitteeReportController::class, 'members'])
            ->name('committees.reports.members');
    });
});

require __DIR__.'/settings.php';
