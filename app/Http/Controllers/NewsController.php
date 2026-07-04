<?php

namespace App\Http\Controllers;

use App\Concerns\FiltersCatalog;
use App\Concerns\SyncsImageUploads;
use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    use FiltersCatalog;
    use SyncsImageUploads;

    /**
     * Supported sort modes for the news feed, in display order.
     *
     * @var list<string>
     */
    private const NEWS_SORTS = ['newest', 'oldest', 'title'];

    /**
     * Show the public news feed of all published posts.
     */
    public function index(Request $request): Response
    {
        ['search' => $search, 'tag' => $tagId, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::NEWS_SORTS, 'newest');

        $posts = Post::query()
            ->with(['club:id,name', 'media'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->withTag($tagId)
            ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['title', 'body']))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('published_at'))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('published_at'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->limit(50)
            ->get(['id', 'title', 'body', 'published_at', 'club_id'])
            ->map(fn (Post $post) => $this->toCardArray($post))
            ->values();

        return Inertia::render('NewsPage', [
            'posts' => $posts,
            'filters' => $this->catalogFilterProps($filters),
            'filterOptions' => [
                'tags' => $this->tagOptions('posts', fn (Builder $query) => $query->whereNotNull('published_at')->where('published_at', '<=', now())),
                'sorts' => $this->sortOptions(self::NEWS_SORTS, 'news.sort_options'),
            ],
        ]);
    }

    /**
     * Show a single published post.
     */
    public function show(Post $post): Response
    {
        if ($post->published_at === null || $post->published_at->isFuture()) {
            abort(404);
        }

        $post->load(['club:id,name', 'author:id,name', 'media']);

        $related = Post::query()
            ->with(['club:id,name', 'media'])
            ->where('club_id', $post->club_id)
            ->whereKeyNot($post->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'body', 'published_at', 'club_id'])
            ->map(fn (Post $related) => $this->toCardArray($related))
            ->values();

        return Inertia::render('NewsArticle', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'images' => $post->imageUrls(),
                'published_at' => $post->published_at->locale(app()->getLocale())->translatedFormat('d F Y'),
                'club' => $post->club?->only(['id', 'name']),
                'author' => $post->author?->name,
            ],
            'related' => $related,
        ]);
    }

    /**
     * Show the form for creating a new post for a club, or for a committee
     * within it when a {committee} is bound on the route.
     */
    public function create(Club $club, ?Committee $committee = null): Response
    {
        $this->authorizeManageNews($club, $committee);

        return Inertia::render('NewsForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee?->only(['id', 'name']),
            'mode' => 'create',
        ]);
    }

    /**
     * Store a newly created post for a club or committee.
     */
    public function store(StorePostRequest $request, Club $club, ?Committee $committee = null): RedirectResponse
    {
        $post = Post::create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'club_id' => $club->id,
            'committee_id' => $committee?->id,
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        $this->syncImageGallery($post, Post::IMAGE_COLLECTION, $request->file('images', []));

        $post->load('club');

        $this->notifyMembersOfNewPost($post, $club, $committee);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.created'),
        ]);

        return $this->manageRedirect($club, $committee);
    }

    /**
     * Show the form for editing an existing post.
     */
    public function edit(Club $club, ?Committee $committee = null, ?Post $post = null): Response
    {
        $this->authorizeManageNews($club, $committee);

        $this->ensurePostScope($post, $club, $committee);

        $post->load('media');

        return Inertia::render('NewsForm', [
            'club' => $club->only(['id', 'name']),
            'committee' => $committee?->only(['id', 'name']),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'images' => $post->imageGallery(),
            ],
            'mode' => 'edit',
        ]);
    }

    /**
     * Update an existing post.
     */
    public function update(UpdatePostRequest $request, Club $club, ?Committee $committee = null, ?Post $post = null): RedirectResponse
    {
        $this->authorizeManageNews($club, $committee);

        $this->ensurePostScope($post, $club, $committee);

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

        return $this->manageRedirect($club, $committee);
    }

    /**
     * Delete a post. Authorizes against the post's committee when it has one,
     * otherwise its club.
     */
    public function destroy(Post $post): RedirectResponse
    {
        if ($post->committee_id !== null) {
            $this->authorize(CommitteeCapability::ManageNews->value, $post->committee);
        } else {
            $this->authorize(ClubCapability::ManageNews->value, $post->club);
        }

        // Associated media is removed automatically when the post is deleted.
        $post->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('news.deleted'),
        ]);

        return back();
    }

    /**
     * Authorize news management against the committee when one is bound,
     * otherwise against the club.
     */
    private function authorizeManageNews(Club $club, ?Committee $committee): void
    {
        $this->authorize(
            $committee !== null ? CommitteeCapability::ManageNews->value : ClubCapability::ManageNews->value,
            $committee ?? $club,
        );
    }

    /**
     * Ensure the post belongs to the bound club (and committee, when present).
     */
    private function ensurePostScope(?Post $post, Club $club, ?Committee $committee): void
    {
        abort_if($post === null, 404);
        abort_unless($post->club_id === $club->id, 404);

        if ($committee !== null) {
            abort_unless($post->committee_id === $committee->id, 404);
        }
    }

    /**
     * Notify approved non-manager members of the relevant club or committee that
     * a new post was published.
     */
    private function notifyMembersOfNewPost(Post $post, Club $club, ?Committee $committee): void
    {
        if ($committee !== null) {
            $recipientIds = CommitteeMembership::query()
                ->where('committee_id', $committee->id)
                ->where('status', 'approved')
                ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', CommitteeRole::managerRoleValues()))
                ->pluck('user_id');
        } else {
            $recipientIds = ClubMembership::query()
                ->where('club_id', $club->id)
                ->where('status', 'approved')
                ->whereDoesntHave('roles', fn ($query) => $query->whereIn('role', ClubRole::managerRoleValues()))
                ->pluck('user_id');
        }

        $recipientIds->each(function (int $userId) use ($post): void {
            User::find($userId)?->notify(new NewPostNotification($post));
        });
    }

    /**
     * Redirect back to the committee dashboard when managing a committee post,
     * otherwise the club dashboard.
     */
    private function manageRedirect(Club $club, ?Committee $committee): RedirectResponse
    {
        return $committee !== null
            ? redirect()->route('committees.updates.index', [$club, $committee])
            : redirect()->route('clubs.manage', $club);
    }

    /**
     * Build the compact card representation of a post shared by the feed and detail pages.
     *
     * @return array{id: int, title: string, excerpt: string, published_at: string|null, club: string|null, image_url: string|null}
     */
    private function toCardArray(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 160),
            'published_at' => $post->published_at?->locale(app()->getLocale())->diffForHumans(),
            'club' => $post->club?->name,
            'image_url' => $post->coverImageUrl(),
        ];
    }
}
