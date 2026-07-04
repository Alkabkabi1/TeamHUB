<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeCapability;
use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Committee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommitteeResourceController extends Controller
{
    public function store(Request $request, Club $club, Committee $committee): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageCommittee->value, $committee);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'string', Rule::in([ClubResource::TYPE_DOWNLOAD, ClubResource::TYPE_MEDIA])],
            'access' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $path = $request->file('file')->store("committee-resources/{$committee->id}", 'public');
        $extension = strtoupper((string) $request->file('file')->getClientOriginalExtension());

        ClubResource::create([
            'club_id' => $club->id,
            'committee_id' => $committee->id,
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

    public function destroy(Club $club, Committee $committee, ClubResource $resource): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageCommittee->value, $committee);

        abort_unless($resource->club_id === $club->id && $resource->committee_id === $committee->id, 404);

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
