<?php

use App\Support\DemoRoles;

return [

    /*
    |--------------------------------------------------------------------------
    | Quick Demo Login
    |--------------------------------------------------------------------------
    |
    | When enabled, the app opens on a passwordless role picker instead of a
    | login form. Visitors choose a seeded demo persona and land in Team Hub.
    | Set DEMO_QUICK_LOGIN=false to restore the normal login/register flow.
    |
    */

    'quick_login' => (bool) env('DEMO_QUICK_LOGIN', true),

    /*
    |--------------------------------------------------------------------------
    | Hourly Demo Data Reset
    |--------------------------------------------------------------------------
    |
    | When enabled, the scheduler rebuilds the database every hour with a fresh
    | seed (migrate:fresh --seed) so the staging demo always returns to a known
    | state. DESTRUCTIVE — set DEMO_HOURLY_RESET=true ONLY on the staging
    | deployment; it must never be enabled in production. The command itself
    | also hard-refuses to run in the production environment as a backstop.
    |
    */

    'hourly_reset' => (bool) env('DEMO_HOURLY_RESET', false),

    /*
    |--------------------------------------------------------------------------
    | Quick Login Accounts
    |--------------------------------------------------------------------------
    |
    | The allowlist of seeded accounts offered by the quick switcher. Each
    | entry maps a DemoUsersSeeder email to a role label key (resolved from
    | lang/{locale}/auth.php under `demo_roles`). Only emails listed here may
    | be used to passwordless-login, and only when the user actually exists.
    |
    | @var list<array{email: string, role: string}>
    */

    'accounts' => DemoRoles::accounts(),

];
