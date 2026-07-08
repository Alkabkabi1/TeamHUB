<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectsOverviewController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $query = $request->only(['q', 'workspace']);

        return redirect()->to(
            route('dashboard', absolute: false).(count($query) > 0 ? '?'.http_build_query($query) : ''),
        );
    }
}
