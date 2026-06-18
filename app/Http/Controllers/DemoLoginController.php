<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DemoLoginController extends Controller
{
    /**
     * Sign in as one of the seeded demo accounts without a password.
     *
     * This is a walkthrough convenience for the demo deployment only. It is
     * gated behind the `demo.quick_login` flag and restricted to the curated
     * allowlist in `config/demo.php`, so it can never authenticate an
     * arbitrary user.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless((bool) config('demo.quick_login'), 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $allowed = collect(config('demo.accounts'))
            ->pluck('email')
            ->contains($validated['email']);

        $user = $allowed
            ? User::query()->where('email', $validated['email'])->first()
            : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('auth.demo_unavailable'),
            ]);
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended($user->homeUrl());
    }
}
