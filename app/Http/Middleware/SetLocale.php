<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    /**
     * The locale every visitor receives unless they have explicitly opted into
     * another supported locale. Hardcoded so Arabic stays the default for all
     * users and crawlers regardless of environment configuration.
     */
    private const DEFAULT_LOCALE = 'ar';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        // No explicit cookie yet: fall back to the authenticated user's saved
        // preference (which follows them across devices) before the default.
        if (! is_string($locale) || ! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $stored = $request->user()?->locale;

            $locale = (is_string($stored) && in_array($stored, self::SUPPORTED_LOCALES, true))
                ? $stored
                : self::DEFAULT_LOCALE;
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
