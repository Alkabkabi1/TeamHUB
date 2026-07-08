<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function show(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->canManageWorkspace($workspace)) {
            return redirect()->route('workspaces.manage', $workspace);
        }

        if ($user !== null) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }
}
