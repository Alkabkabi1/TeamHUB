<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use App\Services\ClubSupervisorReportService;
use Inertia\Inertia;
use Inertia\Response;

class ClubManagementController extends Controller
{
    public function __construct(
        private readonly ClubSupervisorReportService $reports,
    ) {}

    /**
     * Show the club management dashboard. Club-scoped: any user holding at
     * least one club capability (or university staff, who bypass) may open it,
     * and the page renders each section according to the capabilities passed.
     */
    public function index(Club $club): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageClub($club), 403);

        return Inertia::render('clubs/Manage', $this->managementPayload($club, $user));
    }

    public function members(Club $club): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageClub($club), 403);

        $payload = $this->managementPayload($club, $user);

        return Inertia::render('clubs/Members', [
            'theme' => $payload['theme'],
            'club' => $payload['club'],
            'capabilities' => $payload['capabilities'],
            'canManageRoles' => $payload['canManageRoles'],
            'roleOptions' => $payload['roleOptions'],
            'members' => $payload['members'],
            'pendingApplications' => $payload['pendingApplications'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function managementPayload(Club $club, User $user): array
    {
        $club->loadMissing('university:id,name');

        // University staff bypass every club gate; surface the full capability
        // set so their UI mirrors a club lead's. Everyone else gets the union
        // of their roles' capabilities within this club.
        $capabilities = $user->isUniversityStaff()
            ? ClubCapability::values()
            : $user->clubCapabilitiesFor($club)->map(fn (ClubCapability $capability): string => $capability->value)->values()->all();

        $pastEvents = $this->reports->pastEventsForClub($club);
        $eligibleAttendees = $this->reports->eligibleAttendeesForClub($club, $pastEvents);
        $members = $this->reports->clubMembersForManagement($club);
        $projectIds = $club->committees()->pluck('id');

        $workspaceTaskQuery = Task::query()->whereIn('committee_id', $projectIds);
        $workspaceProjects = $club->committees()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done']),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Committee $committee): array => [
                'id' => $committee->id,
                'name' => $committee->name,
                'description' => $committee->description,
                'status' => $committee->status->value,
                'logo_url' => $committee->logo_url,
                'members_count' => $committee->members_count,
                'tasks_count' => $committee->tasks_count,
                'overdue_tasks_count' => $committee->overdue_tasks_count,
            ])
            ->values();

        $recentActivity = collect([
            ...Task::query()
                ->whereIn('committee_id', $projectIds)
                ->with('committee:id,name')
                ->latest('updated_at')
                ->limit(6)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => "task-{$task->id}",
                    'type' => 'task',
                    'title' => $task->title,
                    'context' => $task->committee?->name ?? '',
                    'time' => $task->updated_at?->diffForHumans(),
                    'sort_at' => $task->updated_at?->timestamp ?? 0,
                    'url' => route('committees.tasks.show', [$club, $task->committee_id, $task]),
                ]),
            ...Post::query()
                ->where('club_id', $club->id)
                ->latest('published_at')
                ->limit(4)
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => "post-{$post->id}",
                    'type' => 'update',
                    'title' => $post->title,
                    'context' => $club->name,
                    'time' => $post->published_at?->diffForHumans(),
                    'sort_at' => $post->published_at?->timestamp ?? 0,
                    'url' => route('news.show', $post),
                ]),
        ])->sortByDesc('sort_at')->take(8)->map(function (array $item): array {
            unset($item['sort_at']);

            return $item;
        })->values();

        return [
            // Override the shared university brand with this club's color when set.
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'theme' => $club->theme,
                'logo_url' => $club->logo_url,
                'university' => $club->university?->name,
            ],
            'capabilities' => $capabilities,
            'canManageRoles' => $user->can(ClubCapability::ManageClub->value, $club),
            'roleOptions' => collect(ClubRole::cases())
                ->map(fn (ClubRole $role): array => [
                    'value' => $role->value,
                    'label' => __($role->label()),
                    'isManager' => $role->isManager(),
                ])
                ->values(),
            'pastEvents' => $pastEvents,
            'eligibleAttendees' => $eligibleAttendees,
            'hasDefaultTemplate' => $club->defaultCertificateTemplate() !== null,
            'certificateTemplates' => $club->certificateTemplates()
                ->with(['media', 'placeholders'])
                ->latest()
                ->get()
                ->map(fn (CertificateTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'status' => $template->status,
                    'is_default' => $template->is_default,
                    'image_url' => $template->imageUrl(),
                    'width' => $template->width,
                    'height' => $template->height,
                    'fields_count' => $template->placeholders->count(),
                    'fields' => $template->placeholders->map(fn ($placeholder): array => [
                        'text' => $placeholder->static_text ?: __($placeholder->binding->label()),
                        'is_image' => $placeholder->binding->isImage(),
                        'x' => (float) $placeholder->x,
                        'y' => (float) $placeholder->y,
                        'width' => (float) $placeholder->width,
                        'font_size' => (float) $placeholder->font_size,
                        'align' => $placeholder->align,
                        'color' => $placeholder->color,
                        'font_weight' => $placeholder->font_weight,
                    ])->all(),
                ])
                ->values(),
            'stats' => $this->reports->clubStats($club, $members->count()),
            'workspaceStats' => [
                'projects_count' => $workspaceProjects->count(),
                'tasks_count' => (clone $workspaceTaskQuery)->count(),
                'overdue_tasks_count' => (clone $workspaceTaskQuery)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done'])
                    ->count(),
            ],
            'workspaceProjects' => $workspaceProjects,
            'recentActivity' => $recentActivity,
            'members' => $members,
            'pendingApplications' => ClubJoinApplication::query()
                ->where('club_id', $club->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(fn (ClubJoinApplication $application) => [
                    'id' => $application->id,
                    'name' => $application->full_name,
                    'details' => "{$application->major} - {$application->level}",
                    'time' => $application->created_at?->diffForHumans(),
                ])
                ->values(),
            'managedEvents' => Event::query()
                ->where('club_id', $club->id)
                ->withCount('attendances')
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'ends_at' => $event->ends_at?->toIso8601String(),
                    'location' => $event->location,
                    'capacity' => $event->capacity,
                    'status' => $event->status->value,
                    'attendances_count' => $event->attendances_count,
                    'scannable' => $event->isScannable(),
                ])
                ->values(),
            'posts' => Post::query()
                ->where('club_id', $club->id)
                ->orderByDesc('published_at')
                ->limit(10)
                ->get()
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String(),
                ])
                ->values(),
        ];
    }
}
