<?php

namespace App\Http\Controllers;

use App\Concerns\SyncsImageUploads;
use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    use SyncsImageUploads;

    public function create(Club $club, Committee $committee): Response
    {
        $this->authorize(CommitteeCapability::ManageNews->value, $committee);

        return Inertia::render('NewsForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee->only(['id', 'name']),
            'mode' => 'create',
        ]);
    }

    public function store(StorePostRequest $request, Club $club, Committee $committee): RedirectResponse
    {
        $post = Post::create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'club_id' => $club->id,
            'committee_id' => $committee->id,
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        $this->syncImageGallery($post, Post::IMAGE_COLLECTION, $request->file('images', []));

        $post->load('club');

        $this->notifyMembersOfNewPost($post, $committee);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.created'),
        ]);

        return redirect()->route('committees.updates.index', [$club, $committee]);
    }

    public function edit(Club $club, Committee $committee, Post $post): Response
    {
        $this->authorize(CommitteeCapability::ManageNews->value, $committee);

        abort_unless($post->club_id === $club->id && $post->committee_id === $committee->id, 404);

        $post->load('media');

        return Inertia::render('NewsForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee->only(['id', 'name']),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'images' => $post->imageGallery(),
            ],
            'mode' => 'edit',
        ]);
    }

    public function update(UpdatePostRequest $request, Club $club, Committee $committee, Post $post): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageNews->value, $committee);

        abort_unless($post->club_id === $club->id && $post->committee_id === $committee->id, 404);

        $post->update([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
        ]);

        $this->syncImageGallery(
            $post,
            Post::IMAGE_COLLECTION,
            $request->file('images', []),
            $request->input('removed_media', []),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.updated'),
        ]);

        return redirect()->route('committees.updates.index', [$club, $committee]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        abort_unless($post->committee_id !== null, 404);

        $this->authorize(CommitteeCapability::ManageNews->value, $post->committee);

        $post->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.deleted'),
        ]);

        return back();
    }

    private function notifyMembersOfNewPost(Post $post, Committee $committee): void
    {
        $recipientIds = CommitteeMembership::query()
            ->where('committee_id', $committee->id)
            ->where('status', 'approved')
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', CommitteeRole::managerRoleValues()))
            ->pluck('user_id');

        $recipientIds->each(function (int $userId) use ($post): void {
            User::find($userId)?->notify(new NewPostNotification($post));
        });
    }
}
