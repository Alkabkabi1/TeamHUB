<?php

namespace App\Http\Controllers;

use App\Concerns\FiltersCatalog;
use App\Concerns\SyncsImageUploads;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use FiltersCatalog;
    use SyncsImageUploads;

    /** @var list<string> */
    private const PROJECT_SORTS = ['members', 'newest', 'name'];

    public function index(Request $request, Workspace $workspace): Response
    {
        ['search' => $search, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::PROJECT_SORTS, 'members');

        $projects = $workspace->projects()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
            ])
            ->with('media')
            ->where('status', ProjectStatus::Active->value)
            ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['name', 'description']))
            ->when($sort === 'members', fn ($query) => $query->orderByDesc('members_count')->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->get()
            ->map(fn (Project $project) => $this->toCardArray($project))
            ->values();

        return Inertia::render('projects/Index', [
            'theme' => ['brand' => $workspace->theme ?: config('theme.brand')],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'projects' => $projects,
            'canManage' => $request->user()?->can('update', $workspace) ?? false,
            'filters' => [
                'search' => $filters['search'],
                'sort' => $filters['sort'],
            ],
            'filterOptions' => [
                'sorts' => $this->sortOptions(self::PROJECT_SORTS, 'project.sort_options'),
            ],
        ]);
    }

    public function show(Request $request, Workspace $workspace, Project $project): Response
    {
        abort_unless($project->workspace_id === $workspace->id, 404);

        $project->loadCount([
            'memberships as members_count',
            'tasks as tasks_count',
        ]);

        $openTasksCount = Task::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['done'])
            ->count();

        $recentUpdates = ProjectUpdate::query()
            ->where('project_id', $project->id)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (ProjectUpdate $update) => [
                'id' => $update->id,
                'title' => $update->title,
                'excerpt' => mb_substr(strip_tags((string) $update->body), 0, 160),
                'published_at' => $update->published_at?->locale(app()->getLocale())->diffForHumans(),
                'url' => route('projects.updates.index', [$workspace, $project], absolute: false),
            ])
            ->values();

        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('ProjectPage', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'workspace' => $workspace->only(['id', 'name', 'theme', 'logo_url']),
            'project' => [
                ...$project->only(['id', 'name', 'description', 'theme', 'logo_url', 'status']),
                'image_url' => $project->coverImageUrl(),
            ],
            'canManage' => $user?->canManageProject($project) ?? false,
            'membershipStatus' => $this->membershipStatusFor($user, $project),
            'canRequestToJoin' => $this->canRequestToJoin($user, $workspace, $project),
            'stats' => [
                'members_count' => $project->members_count,
                'tasks_count' => $project->tasks_count,
                'open_tasks_count' => $openTasksCount,
            ],
            'recentUpdates' => $recentUpdates,
        ]);
    }

    public function create(Request $request, Workspace $workspace): Response
    {
        $this->authorize('create', [Project::class, $this->draftFor($workspace)]);

        return Inertia::render('projects/Form', [
            'theme' => ['brand' => $workspace->theme ?: config('theme.brand')],
            'workspace' => $workspace->only(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('create', [Project::class, $this->draftFor($workspace)]);

        $validated = $this->validateProject($request, $workspace);

        $project = $workspace->projects()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? ProjectStatus::Active->value,
        ]);

        if ($request->hasFile('image')) {
            $this->syncImageGallery($project, Project::LOGO_COLLECTION, [$request->file('image')]);
        }

        $this->makeCreatorLead($request->user(), $workspace, $project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.created')]);

        return redirect()->route('projects.manage', [$workspace, $project]);
    }

    public function edit(Request $request, Workspace $workspace, Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Form', [
            'theme' => ['brand' => $project->theme ?: ($workspace->theme ?: config('theme.brand'))],
            'workspace' => $workspace->only(['id', 'name']),
            'project' => [
                ...$project->only(['id', 'name', 'description', 'status']),
                'image_url' => $project->logo_url,
            ],
            'statusOptions' => $this->statusOptions(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $this->validateProject($request, $workspace, $project);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? $project->status->value,
        ]);

        if ($request->boolean('remove_image')) {
            $project->clearMediaCollection(Project::LOGO_COLLECTION);
        }

        if ($request->hasFile('image')) {
            $this->syncImageGallery($project, Project::LOGO_COLLECTION, [$request->file('image')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.updated')]);

        return redirect()->route('projects.manage', [$workspace, $project]);
    }

    public function destroy(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.archived')]);

        return redirect()->route('projects.index', $workspace);
    }

    /**
     * @return array{id: int, name: string, description: string, image_url: string|null, members_count: int, tasks_count: int}
     */
    private function toCardArray(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => mb_substr(strip_tags((string) $project->description), 0, 160),
            'image_url' => $project->logo_url ?: $project->coverImageUrl(),
            'members_count' => $project->members_count,
            'tasks_count' => $project->tasks_count,
        ];
    }

    private function draftFor(Workspace $workspace): Project
    {
        $project = new Project(['workspace_id' => $workspace->id]);
        $project->setRelation('workspace', $workspace);

        return $project;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ProjectStatus $status): array => ['value' => $status->value, 'label' => __($status->label())],
            ProjectStatus::cases(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProject(Request $request, Workspace $workspace, ?Project $project = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('projects', 'name')
                    ->where('workspace_id', $workspace->id)
                    ->ignore($project?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(ProjectStatus::values())],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ], [
            'name.required' => __('project.validation.name.required'),
            'name.unique' => __('project.validation.name.unique'),
            'image.image' => __('project.validation.image.image'),
            'image.mimes' => __('project.validation.image.mimes'),
            'image.max' => __('project.validation.image.max'),
        ]);
    }

    private function makeCreatorLead(User $user, Workspace $workspace, Project $project): void
    {
        if ($user->workspaceMembershipFor($workspace) === null) {
            return;
        }

        $membership = $project->memberships()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_at' => now(),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $membership->assignProjectRole(ProjectRole::ProjectLead);
        $membership->assignProjectRole(ProjectRole::Member);
    }

    private function membershipStatusFor(?User $user, Project $project): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user->projectMemberships()
            ->where('project_id', $project->id)
            ->value('status');
    }

    private function canRequestToJoin(?User $user, Workspace $workspace, Project $project): bool
    {
        if ($user === null || ! $user->isMember()) {
            return false;
        }

        return $user->workspaceMembershipFor($workspace) !== null
            && $this->membershipStatusFor($user, $project) === null;
    }
}
