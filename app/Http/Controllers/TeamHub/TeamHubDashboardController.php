<?php

namespace App\Http\Controllers\TeamHub;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DemoRoles;
use App\Support\DemoWalkthroughBootstrap;
use App\Support\TeamHub\TeamHubDashboardPresenter;
use App\Support\TeamHub\TeamHubData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamHubDashboardController extends Controller
{
    public function __construct(
        private TeamHubData $hub,
        private TeamHubDashboardPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $persona = DemoRoles::find($user->email)['role'] ?? null;

        if ($persona !== null) {
            DemoWalkthroughBootstrap::ensure($user);
            $user->refresh();
        }

        $dashboard = $this->presenter->forUser($user, $persona);

        $lateTasks = $this->hub->tasksQuery($user)
            ->overdue()
            ->limit(5)
            ->get()
            ->map(fn ($task) => $this->hub->presentTask($task));

        return Inertia::render('team-hub/Dashboard', [
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
