<?php

namespace App\Http\Controllers;

use App\Enums\ProjectCapability;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectFileController extends Controller
{
    public function store(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageProject->value, $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'string', Rule::in([ProjectFile::TYPE_DOWNLOAD, ProjectFile::TYPE_MEDIA])],
            'access' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $path = $request->file('file')->store("project-files/{$project->id}", 'public');
        $extension = strtoupper((string) $request->file('file')->getClientOriginalExtension());

        ProjectFile::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'format' => $extension !== '' ? $extension : 'FILE',
            'access' => $validated['access'],
            'file_path' => $path,
            'published_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('resources.file_uploaded'),
        ]);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, ProjectFile $resource): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageProject->value, $project);

        abort_unless($resource->workspace_id === $workspace->id && $resource->project_id === $project->id, 404);

        if ($resource->file_path !== null && Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('resources.file_deleted'),
        ]);

        return back();
    }
}
