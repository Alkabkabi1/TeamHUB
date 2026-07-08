<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Workspace;
use App\Support\AppNav;
use App\Support\DashboardData;
use App\Support\DemoAccounts;
use App\Support\LoadsTranslations;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
            'translations' => LoadsTranslations::all($locale),
            'name' => config('app.name'),
            'theme' => [
                // University-level default brand color. Club-scoped pages may
                // override this key in their own Inertia::render() props.
                'brand' => config('theme.brand'),
            ],
            'auth' => [
                'user' => $request->user()
                    ? (function () use ($request) {
                        $user = $request->user();

                        // University staff can manage every club and committee;
                        // everyone else gets only the ones they hold a role in.
                        if ($user->isAdmin()) {
                            $managedWorkspaces = Workspace::all()
                                ->map(fn ($workspace) => [
                                    'id' => $workspace->id,
                                    'name' => $workspace->name,
                                    'logo_url' => $workspace->logo_url,
                                ])
                                ->values();

                            $managedProjects = Project::all()
                                ->map(fn ($project) => [
                                    'id' => $project->id,
                                    'name' => $project->name,
                                    'workspace_id' => $project->workspace_id,
                                ])
                                ->values();
                        } else {
                            $managedWorkspaces = $user->managedWorkspaces()
                                ->map(fn ($workspace) => [
                                    'id' => $workspace->id,
                                    'name' => $workspace->name,
                                    'logo_url' => $workspace->logo_url,
                                ])
                                ->values();

                            $managedProjects = $user->managedProjects()
                                ->map(fn ($project) => [
                                    'id' => $project->id,
                                    'name' => $project->name,
                                    'workspace_id' => $project->workspace_id,
                                ])
                                ->values();
                        }

                        $unreadCount = once(fn (): int => $user->unreadNotifications()->count());

                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role->value,
                            'unread_notifications_count' => $unreadCount,
                            'managed_workspaces' => $managedWorkspaces,
                            'is_workspace_lead' => $managedWorkspaces->isNotEmpty(),
                            'managed_projects' => $managedProjects,
                            'is_project_lead' => $managedProjects->isNotEmpty(),
                        ];
                    })()
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'app' => $request->user()
                ? (function () use ($request) {
                    $user = $request->user();
                    $dashboardData = app(DashboardData::class);

                    return [
                        'nav' => AppNav::items($user),
                        'workspaces' => $dashboardData->workspaces($user),
                    ];
                })()
                : null,
            'demo' => [
                'quick_login' => (bool) config('demo.quick_login'),
                'accounts' => DemoAccounts::forSwitcher()->all(),
            ],
        ];
    }
}
