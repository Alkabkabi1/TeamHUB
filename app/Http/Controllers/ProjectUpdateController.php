<?php

namespace App\Http\Controllers;

use App\Concerns\SyncsImageUploads;
use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Http\Requests\StoreProjectUpdateRequest;
use App\Http\Requests\UpdateProjectUpdateRequest;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\NewPostNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectUpdateController extends Controller
{
    use SyncsImageUploads;

    public function create(Workspace $workspace, Project $project): Response
    {
        $this->authorize(ProjectCapability::ManageUpdates->value, $project);

        return Inertia::render('NewsForm', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name']),
            'mode' => 'create',
        ]);
    }

    public function store(StoreProjectUpdateRequest $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $update = ProjectUpdate::create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        $this->syncImageGallery($update, ProjectUpdate::IMAGE_COLLECTION, $request->file('images', []));

        $update->load('workspace');

        $this->notifyMembersOfNewUpdate($update, $project);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.created'),
        ]);

        return redirect()->route('projects.updates.index', [$workspace, $project]);
    }

    public function edit(Workspace $workspace, Project $project, ProjectUpdate $post): Response
    {
        $this->authorize(ProjectCapability::ManageUpdates->value, $project);

        abort_unless($post->workspace_id === $workspace->id && $post->project_id === $project->id, 404);

        $post->load('media');

        return Inertia::render('NewsForm', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name']),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'images' => $post->imageGallery(),
            ],
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateProjectUpdateRequest $request, Workspace $workspace, Project $project, ProjectUpdate $post): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageUpdates->value, $project);

        abort_unless($post->workspace_id === $workspace->id && $post->project_id === $project->id, 404);

        $post->update([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
        ]);

        $this->syncImageGallery(
            $post,
            ProjectUpdate::IMAGE_COLLECTION,
            $request->file('images', []),
            $request->input('removed_media', []),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.updated'),
        ]);

        return redirect()->route('projects.updates.index', [$workspace, $project]);
    }

    public function destroy(ProjectUpdate $post): RedirectResponse
    {
        abort_unless($post->project_id !== null, 404);

        $this->authorize(ProjectCapability::ManageUpdates->value, $post->project);

        $post->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.deleted'),
        ]);

        return back();
    }

    private function notifyMembersOfNewUpdate(ProjectUpdate $update, Project $project): void
    {
        $recipientIds = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', ProjectRole::managerRoleValues()))
            ->pluck('user_id');

        $recipientIds->each(function (int $userId) use ($update): void {
            User::find($userId)?->notify(new NewPostNotification($update));
        });
    }
}
