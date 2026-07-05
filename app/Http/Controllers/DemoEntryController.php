<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DemoEntryController extends Controller
{
    /**
     * Passwordless entry: pick a demo role and jump straight into Team Hub.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        abort_unless((bool) config('demo.quick_login'), 404);

        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('team-hub/Entry');
    }
}
