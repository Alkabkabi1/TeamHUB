<?php

namespace App\Http\Controllers;

use App\Concerns\FiltersCatalog;
use App\Concerns\SyncsImageUploads;
use App\Enums\CommitteeRole;
use App\Enums\CommitteeStatus;
use App\Models\Club;
use App\Models\Committee;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeController extends Controller
{
    use FiltersCatalog;
    use SyncsImageUploads;

    /**
     * Supported sort modes for the committees listing, in display order.
     *
     * @var list<string>
     */
    private const COMMITTEE_SORTS = ['members', 'newest', 'name'];

    /**
     * Public listing of a club's committees (Figma 506-1910).
     */
    public function index(Request $request, Club $club): Response
    {
        ['search' => $search, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::COMMITTEE_SORTS, 'members');

        $committees = $club->committees()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
            ])
            ->with('media')
            ->where('status', CommitteeStatus::Active->value)
            ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['name', 'description']))
            ->when($sort === 'members', fn ($query) => $query->orderByDesc('members_count')->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->get()
            ->map(fn (Committee $committee) => $this->toCardArray($committee))
            ->values();

        return Inertia::render('committees/Index', [
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url', 'category', 'college']),
            'committees' => $committees,
            'canManage' => $request->user()?->can('update', $club) ?? false,
            'filters' => [
                'search' => $filters['search'],
                'sort' => $filters['sort'],
            ],
            'filterOptions' => [
                'sorts' => $this->sortOptions(self::COMMITTEE_SORTS, 'committees.sort_options'),
            ],
        ]);
    }

    /**
     * Public committee page with stats, events and news (Figma 507-2214).
     */
    public function show(Request $request, Club $club, Committee $committee): Response
    {
        $committee->loadCount([
            'memberships as members_count',
            'tasks as tasks_count',
        ]);

        $openTasksCount = Task::query()
            ->where('committee_id', $committee->id)
            ->whereNotIn('status', ['done'])
            ->count();

        $recentUpdates = Post::query()
            ->where('committee_id', $committee->id)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 160),
                'published_at' => $post->published_at?->locale(app()->getLocale())->diffForHumans(),
                'url' => route('committees.updates.index', [$club, $committee], absolute: false),
            ])
            ->values();

        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('CommitteePage', [
            'theme' => ['brand' => $committee->theme ?: ($club->theme ?: config('theme.brand'))],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url']),
            'committee' => [
                ...$committee->only(['id', 'name', 'description', 'theme', 'logo_url', 'status']),
                'image_url' => $committee->coverImageUrl(),
            ],
            'canManage' => $user?->canManageCommittee($committee) ?? false,
            'membershipStatus' => $this->membershipStatusFor($user, $committee),
            'canRequestToJoin' => $this->canRequestToJoin($user, $club, $committee),
            'stats' => [
                'members_count' => $committee->members_count,
                'tasks_count' => $committee->tasks_count,
                'open_tasks_count' => $openTasksCount,
            ],
            'recentUpdates' => $recentUpdates,
        ]);
    }

    /**
     * Show the form to create a committee (club leads + staff).
     */
    public function create(Request $request, Club $club): Response
    {
        $this->authorize('create', [Committee::class, $this->draftFor($club)]);

        return Inertia::render('committees/Form', [
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => $club->only(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'mode' => 'create',
        ]);
    }

    /**
     * Store a new committee. The creator becomes its lead when they are an
     * approved member of the parent club, so the committee is never leaderless.
     */
    public function store(Request $request, Club $club): RedirectResponse
    {
        $this->authorize('create', [Committee::class, $this->draftFor($club)]);

        $validated = $this->validateCommittee($request, $club);

        $committee = $club->committees()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? CommitteeStatus::Active->value,
        ]);

        if ($request->hasFile('image')) {
            $this->syncImageGallery($committee, Committee::LOGO_COLLECTION, [$request->file('image')]);
        }

        $this->makeCreatorLead($request->user(), $club, $committee);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committees.created')]);

        return redirect()->route('committees.manage', [$club, $committee]);
    }

    /**
     * Show the form to edit a committee.
     */
    public function edit(Request $request, Club $club, Committee $committee): Response
    {
        $this->authorize('update', $committee);

        return Inertia::render('committees/Form', [
            'theme' => ['brand' => $committee->theme ?: ($club->theme ?: config('theme.brand'))],
            'club' => $club->only(['id', 'name']),
            'committee' => [
                ...$committee->only(['id', 'name', 'description', 'status']),
                'image_url' => $committee->logo_url,
            ],
            'statusOptions' => $this->statusOptions(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Update a committee.
     */
    public function update(Request $request, Club $club, Committee $committee): RedirectResponse
    {
        $this->authorize('update', $committee);

        $validated = $this->validateCommittee($request, $club, $committee);

        $committee->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? $committee->status->value,
        ]);

        if ($request->boolean('remove_image')) {
            $committee->clearMediaCollection(Committee::LOGO_COLLECTION);
        }

        if ($request->hasFile('image')) {
            $this->syncImageGallery($committee, Committee::LOGO_COLLECTION, [$request->file('image')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committees.updated')]);

        return redirect()->route('committees.manage', [$club, $committee]);
    }

    /**
     * Archive (soft-delete) a committee — club leads + staff.
     */
    public function destroy(Request $request, Club $club, Committee $committee): RedirectResponse
    {
        $this->authorize('delete', $committee);

        $committee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committees.archived')]);

        return redirect()->route('committees.index', $club);
    }

    /**
     * Compact card representation used by the listing.
     *
     * @return array{id: int, name: string, description: string, image_url: string|null, members_count: int, tasks_count: int}
     */
    private function toCardArray(Committee $committee): array
    {
        return [
            'id' => $committee->id,
            'name' => $committee->name,
            'description' => mb_substr(strip_tags((string) $committee->description), 0, 160),
            'image_url' => $committee->logo_url ?: $committee->coverImageUrl(),
            'members_count' => $committee->members_count,
            'tasks_count' => $committee->tasks_count,
        ];
    }

    /**
     * A transient committee bound to the club, used only so the policy's
     * create() check can read the parent club before the row exists.
     */
    private function draftFor(Club $club): Committee
    {
        $committee = new Committee(['club_id' => $club->id]);
        $committee->setRelation('club', $club);

        return $committee;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CommitteeStatus $status): array => ['value' => $status->value, 'label' => __($status->label())],
            CommitteeStatus::cases(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCommittee(Request $request, Club $club, ?Committee $committee = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('committees', 'name')
                    ->where('club_id', $club->id)
                    ->ignore($committee?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(CommitteeStatus::values())],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ], [
            'name.required' => __('committees.validation.name.required'),
            'name.unique' => __('committees.validation.name.unique'),
            'image.image' => __('committees.validation.image.image'),
            'image.mimes' => __('committees.validation.image.mimes'),
            'image.max' => __('committees.validation.image.max'),
        ]);
    }

    /**
     * Give the creator the CommitteeLead role when they are an approved club
     * member (e.g. a student club lead). Staff manage without a membership row.
     */
    private function makeCreatorLead(User $user, Club $club, Committee $committee): void
    {
        if ($user->clubMembershipFor($club) === null) {
            return;
        }

        $membership = $committee->memberships()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_at' => now(),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $membership->assignCommitteeRole(CommitteeRole::CommitteeLead);
        $membership->assignCommitteeRole(CommitteeRole::Member);
    }

    /**
     * The viewer's committee membership status: approved, pending, or null.
     */
    private function membershipStatusFor(?User $user, Committee $committee): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user->committeeMemberships()
            ->where('committee_id', $committee->id)
            ->value('status');
    }

    /**
     * A user may request to join only when they are an approved member of the
     * parent club and have no committee membership yet.
     */
    private function canRequestToJoin(?User $user, Club $club, Committee $committee): bool
    {
        if ($user === null || ! $user->isStudent()) {
            return false;
        }

        return $user->clubMembershipFor($club) !== null
            && $this->membershipStatusFor($user, $committee) === null;
    }
}
