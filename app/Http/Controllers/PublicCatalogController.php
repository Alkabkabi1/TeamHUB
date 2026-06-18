<?php

namespace App\Http\Controllers;

use App\Concerns\FiltersCatalog;
use App\Enums\EventAttendanceStatus;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\ClubResource;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\VolunteerHour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    use FiltersCatalog;

    /**
     * Supported sort modes for the clubs catalog, in display order.
     *
     * @var list<string>
     */
    private const CLUB_SORTS = ['members', 'newest', 'name'];

    /**
     * Supported sort modes for the events catalog, in display order.
     *
     * @var list<string>
     */
    private const EVENT_SORTS = ['soonest', 'newest', 'title'];

    /**
     * Supported sort modes for the resources catalog, in display order.
     *
     * @var list<string>
     */
    private const RESOURCE_SORTS = ['newest', 'oldest', 'title'];

    /**
     * Show the public clubs catalog.
     */
    public function clubs(Request $request): Response
    {
        ['search' => $search, 'tag' => $tagId, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::CLUB_SORTS, 'members');

        $clubs = Club::query()
            ->withCount('memberships as members_count')
            ->with(['tags:id,name', 'media'])
            ->where('status', 'active')
            ->withTag($tagId)
            ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['name']))
            ->when($sort === 'members', fn ($query) => $query->orderByDesc('members_count')->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->limit(50)
            ->get(['id', 'name', 'theme', 'category', 'college', 'status']);

        $userId = $request->user()?->id;
        $clubIds = $clubs->pluck('id');

        $memberClubIds = $userId
            ? ClubMembership::query()
                ->where('user_id', $userId)
                ->whereIn('club_id', $clubIds)
                ->where('status', 'approved')
                ->pluck('club_id')
                ->merge(
                    ClubJoinApplication::query()
                        ->where('user_id', $userId)
                        ->whereIn('club_id', $clubIds)
                        ->where('status', 'pending')
                        ->pluck('club_id')
                )
                ->unique()
            : collect();

        $clubs->each(function (Club $club) use ($memberClubIds): void {
            $club->is_member = $memberClubIds->contains($club->id);
        });

        return Inertia::render('ClubsPage', [
            'clubs' => $clubs,
            'stats' => [
                'clubs' => Club::query()->where('status', 'active')->count(),
                'members' => ClubMembership::query()->count(),
                'events' => Event::query()->active()->upcoming()->count(),
                'hours' => (float) VolunteerHour::query()->sum('hours'),
            ],
            'filters' => $this->catalogFilterProps($filters),
            'filterOptions' => [
                'tags' => $this->tagOptions('clubs', fn (Builder $query) => $query->where('status', 'active')),
                'sorts' => $this->sortOptions(self::CLUB_SORTS, 'clubs.sort_options'),
            ],
        ]);
    }

    /**
     * Show the public events catalog.
     */
    public function events(Request $request): Response
    {
        ['search' => $search, 'tag' => $tagId, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::EVENT_SORTS, 'soonest');

        $events = Event::query()
            ->with(['club:id,name,category,college,status', 'media'])
            ->withCount(['attendances as registrations_count' => fn ($q) => $q->whereIn('status', EventAttendanceStatus::registeredValues())])
            ->upcoming()
            ->active()
            ->withTag($tagId)
            ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['title', 'description', 'location']))
            ->when($sort === 'soonest', fn ($query) => $query->orderBy('starts_at'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->limit(50)
            ->get(['id', 'club_id', 'title', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'status'])
            ->each->append('image_url');

        $userRsvpIds = $request->user()
            ? EventAttendance::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('event_id', $events->pluck('id'))
                ->whereIn('status', EventAttendanceStatus::registeredValues())
                ->pluck('event_id')
                ->values()
            : collect();

        return Inertia::render('EventsPage', [
            'events' => $events,
            'userRsvpIds' => $userRsvpIds,
            'filters' => $this->catalogFilterProps($filters),
            'filterOptions' => [
                'tags' => $this->tagOptions('events', fn (Builder $query) => $query->upcoming()->active()),
                'sorts' => $this->sortOptions(self::EVENT_SORTS, 'events.sort_options'),
            ],
        ]);
    }

    /**
     * Show the public resources and media catalog.
     */
    public function resources(Request $request): Response
    {
        ['search' => $search, 'tag' => $tagId, 'sort' => $sort] = $filters = $this->catalogFilters($request, self::RESOURCE_SORTS, 'newest');

        $applyFilters = function (Builder $query) use ($search, $tagId, $sort): void {
            $query
                ->withTag($tagId)
                ->tap(fn (Builder $query) => $this->applySearch($query, $search, ['title', 'description']))
                ->when($sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
                ->when($sort === 'oldest', fn ($q) => $q->orderBy('published_at'))
                ->when($sort === 'title', fn ($q) => $q->orderBy('title'));
        };

        $downloads = ClubResource::query()
            ->downloads()
            ->with('club:id,name')
            ->tap($applyFilters)
            ->limit(50)
            ->get()
            ->map(fn (ClubResource $resource) => [
                'id' => $resource->id,
                'name' => $resource->title,
                'description' => $resource->description ?? '',
                'club' => $resource->club?->name ?? '',
                'format' => $resource->format,
                'access' => $resource->access,
                'downloadUrl' => $resource->file_path ? Storage::disk('public')->url($resource->file_path) : null,
            ])
            ->values()
            ->all();

        $media = ClubResource::query()
            ->media()
            ->with('club:id,name')
            ->tap($applyFilters)
            ->limit(50)
            ->get()
            ->map(fn (ClubResource $resource) => [
                'id' => $resource->id,
                'date' => $resource->published_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? '',
                'club' => $resource->club?->name ?? '',
                'title' => $resource->title,
                'format' => $resource->format,
                'access' => $resource->access,
                'downloadUrl' => $resource->file_path ? Storage::disk('public')->url($resource->file_path) : null,
            ])
            ->values()
            ->all();

        return Inertia::render('ResourcesPage', [
            'downloads' => $downloads,
            'media' => $media,
            'filters' => $this->catalogFilterProps($filters),
            'filterOptions' => [
                'tags' => $this->tagOptions('resources'),
                'sorts' => $this->sortOptions(self::RESOURCE_SORTS, 'resources.sort_options'),
            ],
        ]);
    }
}
