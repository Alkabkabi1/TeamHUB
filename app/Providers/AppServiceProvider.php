<?php

namespace App\Providers;

use App\Ai\PendingActionService;
use App\Enums\ProjectCapability;
use App\Enums\WorkspaceCapability;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped so Octane creates a fresh instance per request.
        $this->app->scoped(PendingActionService::class);

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request): Response|RedirectResponse
            {
                // Drop any stale post-login redirect target and signal the login
                // screen to skip recapturing the page the user just left via the
                // referer header, so the next login isn't bounced to it.
                $request->session()->forget('url.intended');

                return redirect()->route('login')->with('skipReturnUrl', true);
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureWorkspaceCapabilities();
        $this->configureErrorPages();
    }

    /**
     * Render branded Inertia error pages for common HTTP error statuses.
     *
     * Client-safe statuses (403/404/419/429/503) render the custom page in
     * every environment so they can be previewed locally. A 500 falls through
     * to Laravel's default handler in local/testing so the stack trace stays
     * visible while debugging, and only shows the branded page in production.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): mixed {
            $status = $response->statusCode();

            $renderable = [403, 404, 419, 429, 503];

            if (! app()->environment(['local', 'testing'])) {
                $renderable[] = 500;
            }

            if (in_array($status, $renderable, true)) {
                return $response->render('ErrorPage', [
                    'status' => $status,
                ])->withSharedData();
            }

            return null;
        });
    }

    protected function configurePolicies(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }

    /**
     * Register a Gate ability per club and committee capability. Each ability
     * authorizes a user against a specific club or committee; university staff
     * bypass both capability sets.
     */
    protected function configureWorkspaceCapabilities(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            $capabilityAbilities = [...WorkspaceCapability::values(), ...ProjectCapability::values()];

            if ($user->isAdmin() && in_array($ability, $capabilityAbilities, true)) {
                return true;
            }

            return null;
        });

        foreach (WorkspaceCapability::cases() as $capability) {
            Gate::define(
                $capability->value,
                fn (User $user, Workspace $workspace): bool => $user->hasWorkspaceCapability($capability, $workspace),
            );
        }

        foreach (ProjectCapability::cases() as $capability) {
            Gate::define(
                $capability->value,
                fn (User $user, Project $project): bool => $user->hasProjectCapability($capability, $project),
            );
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
