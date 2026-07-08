<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TasksOverviewController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $target = $user->usesMyTasksHome()
            ? route('my-tasks', absolute: false)
            : route('dashboard', absolute: false);

        $query = $request->only(['q', 'status', 'workspace']);

        return redirect()->to($target.(count($query) > 0 ? '?'.http_build_query($query) : ''));
    }
}
