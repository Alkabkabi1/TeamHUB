<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DashboardData;
use App\Support\DashboardPresenter;
use App\Support\DemoRoles;
use App\Support\DemoWalkthroughBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardData $hub,
        private DashboardPresenter $presenter,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $persona = DemoRoles::find($user->email)['role'] ?? null;

        if ($persona === 'staff' || ($persona === null && $user->usesMyTasksHome())) {
            return redirect()->route('my-tasks');
        }

        $activeProjectId = $request->integer('project') ?: null;

        if ($persona !== null) {
            DemoWalkthroughBootstrap::ensure($user);
            $user->refresh();
        }

        $dashboard = $this->presenter->forUser($user, $persona, $activeProjectId);

        $lateTasks = $this->hub->tasksQuery($user)
            ->overdue()
            ->limit(5)
            ->get()
            ->map(fn ($task) => $this->hub->presentTask($task));

        return Inertia::render('app/Dashboard', [
            'demoPersona' => $persona,
            'greeting' => $this->presenter->greeting($user, $persona),
            'todayLabel' => now()->locale(app()->getLocale())->translatedFormat('l، j F Y'),
            'dashboard' => $dashboard['panel'],
            'lateTasks' => $lateTasks,
            'activities' => $this->hub->activities($user),
            'calendarMarkers' => $this->hub->calendarMarkers($user),
            'creatableWorkspaces' => $this->hub->creatableWorkspaces($user),
        ]);
    }
}
