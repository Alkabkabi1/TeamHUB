<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceCapability;
use App\Http\Requests\UpdateWorkspaceThemeRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceThemeController extends Controller
{
    /**
     * Show the theme editor for a club.
     */
    public function edit(Workspace $workspace): Response
    {
        $this->authorize(WorkspaceCapability::ManageWorkspace->value, $workspace);

        return Inertia::render('WorkspaceTheme', [
            // Override the shared university brand with this club's color when set.
            'theme' => ['brand' => $workspace->theme ?: config('theme.brand')],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'logoUrl' => $workspace->logo_url,
        ]);
    }

    /**
     * Persist the club's theme color and logo.
     */
    public function update(UpdateWorkspaceThemeRequest $request, Workspace $workspace): RedirectResponse
    {
        if ($request->filled('name')) {
            $workspace->name = $request->validated('name');
        }

        if ($request->hasFile('logo')) {
            // The single-file collection replaces any previous logo.
            $workspace->addMedia($request->file('logo'))->toMediaCollection(Workspace::LOGO_COLLECTION);
        }

        $workspace->theme = $request->validated('theme');
        $workspace->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('theme.success'),
        ]);

        return redirect()->route('workspaces.theme.edit', $workspace);
    }
}
