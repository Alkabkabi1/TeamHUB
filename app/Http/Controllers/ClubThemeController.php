<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Http\Requests\UpdateClubThemeRequest;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClubThemeController extends Controller
{
    /**
     * Show the theme editor for a club.
     */
    public function edit(Club $club): Response
    {
        $this->authorize(ClubCapability::ManageClub->value, $club);

        return Inertia::render('ClubTheme', [
            // Override the shared university brand with this club's color when set.
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url']),
            'logoUrl' => $club->logo_url,
        ]);
    }

    /**
     * Persist the club's theme color and logo.
     */
    public function update(UpdateClubThemeRequest $request, Club $club): RedirectResponse
    {
        if ($request->hasFile('logo')) {
            // The single-file collection replaces any previous logo.
            $club->addMedia($request->file('logo'))->toMediaCollection(Club::LOGO_COLLECTION);
        }

        $club->theme = $request->validated('theme');
        $club->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('theme.success'),
        ]);

        return redirect()->route('clubs.theme.edit', $club);
    }
}
