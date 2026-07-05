<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DemoRoles;
use App\Support\DemoWalkthroughBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemoLoginController extends Controller
{
    /**
     * Sign in as one of the seeded demo accounts without a password.
     *
     * This is a walkthrough convenience for the demo deployment only. It is
     * gated behind the `demo.quick_login` flag and restricted to the curated
     * allowlist in DemoRoles, so it can never authenticate an arbitrary user.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless((bool) config('demo.quick_login'), 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $account = DemoRoles::find($validated['email']);

        if (! $account) {
            throw ValidationException::withMessages([
                'email' => __('auth.demo_unavailable'),
            ]);
        }

        $user = User::query()->where('email', $validated['email'])->first()
            ?? $this->provisionDemoUser($account);

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        DemoWalkthroughBootstrap::ensure($user);

        return redirect()->intended($user->homeUrl());
    }

    /**
     * @param  array{email: string, role: string}  $account
     */
    private function provisionDemoUser(array $account): User
    {
        $role = $account['role'] === 'admin'
            ? UserRole::Admin
            : UserRole::Member;

        return User::query()->create([
            'name' => __("auth.demo_roles.{$account['role']}"),
            'email' => $account['email'],
            'password' => Hash::make(Str::password(32)),
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }
}
