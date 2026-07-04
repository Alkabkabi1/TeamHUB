<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            if (config('demo.quick_login')) {
                return redirect()->route('home');
            }

            $this->rememberReturnUrl($request);

            // Tell the user why they landed on the login screen whenever there
            // is a page waiting for them after authentication.
            if ($request->session()->has('url.intended')) {
                Inertia::flash('toast', [
                    'type' => 'info',
                    'message' => __('auth.login_required'),
                ]);
            }

            return Inertia::render('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'canRegister' => Features::enabled(Features::registration()),
                'status' => $request->session()->get('status'),
                'demoAccounts' => $this->demoAccounts(),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(function (Request $request) {
            if (config('demo.quick_login')) {
                return redirect()->route('home');
            }

            // Preserve the return target when a guest switches from login to
            // register so Fortify's redirect()->intended() still works.
            $this->rememberReturnUrl($request);

            return Inertia::render('auth/Register');
        });

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * The curated demo accounts offered by the login quick-switcher, resolved
     * to the seeded users that actually exist. Returns an empty collection
     * when quick login is disabled so the switcher never renders.
     *
     * @return Collection<int, array{email: string, name: string, role: string}>
     */
    private function demoAccounts(): Collection
    {
        return DemoAccounts::forSwitcher();
    }

    /**
     * Remember the internal page a guest came from so they can be returned to
     * it after authenticating. The auth middleware already sets this when it
     * bounces a guest off a protected route; this covers links to /login from
     * public pages (e.g. the "Register" button on an event), where no
     * middleware redirect happens. Falls back to the referer header.
     */
    private function rememberReturnUrl(Request $request): void
    {
        if ($request->session()->has('url.intended')) {
            return;
        }

        // Right after a logout we don't want to recapture the (now off-limits)
        // page the user left via the referer header and bounce the next login
        // back to it. The logout response flashes this flag for one request.
        if ($request->session()->get('skipReturnUrl')) {
            return;
        }

        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return;
        }

        $parts = parse_url($referer);

        // Same-origin only — never honor an off-site redirect target.
        if (($parts['host'] ?? null) !== $request->getHost()) {
            return;
        }

        $path = $parts['path'] ?? '/';

        // Don't loop back to an auth screen.
        if (in_array($path, ['/login', '/register'], true)) {
            return;
        }

        if (isset($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        $request->session()->put('url.intended', $path);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
