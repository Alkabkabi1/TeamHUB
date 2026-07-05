<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectUpdate;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::bind('workspace', fn (string $value) => Workspace::findOrFail($value));
            Route::bind('project', fn (string $value) => Project::findOrFail($value));
            Route::bind('application', fn (string $value) => WorkspaceMembershipRequest::findOrFail($value));
            Route::bind('post', fn (string $value) => ProjectUpdate::findOrFail($value));
            Route::bind('resource', fn (string $value) => ProjectFile::findOrFail($value));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Coolify's reverse proxy (Caddy/Traefik) — trust forwarded
        // headers so HTTPS scheme/host are detected correctly.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['sidebar_state', 'locale']);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => config('demo.quick_login')
                ? route('home')
                : route('login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
