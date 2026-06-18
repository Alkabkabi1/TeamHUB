<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Handle a support contact / inquiry submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Log::info('Support contact submission', $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('support.message_sent'),
        ]);

        return back();
    }
}
