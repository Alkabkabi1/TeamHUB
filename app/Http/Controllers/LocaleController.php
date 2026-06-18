<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    /**
     * Update the user's locale preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', self::SUPPORTED_LOCALES)],
        ]);

        // Persist the explicit choice for authenticated users so it follows them
        // across devices and is used when rendering their notification emails.
        if ($user = $request->user()) {
            $user->update(['locale' => $validated['locale']]);
        }

        return redirect()
            ->back()
            ->cookie('locale', $validated['locale'], 60 * 24 * 365);
    }
}
