<?php

namespace App\Http\Controllers;

use App\Concerns\FiltersCatalog;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\ClubResource;
use App\Models\Committee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    use FiltersCatalog;

    /** @var list<string> */
    private const CLUB_SORTS = ['members', 'newest', 'name'];

    /** @var list<string> */
    private const RESOURCE_SORTS = ['newest', 'oldest', 'title'];

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
                'projects' => Committee::query()->count(),
                'open_tasks' => Task::query()->whereNotIn('status', ['done'])->count(),
            ],
            'filters' => $this->catalogFilterProps($filters),
            'filterOptions' => [
                'tags' => $this->tagOptions('clubs', fn (Builder $query) => $query->where('status', 'active')),
                'sorts' => $this->sortOptions(self::CLUB_SORTS, 'clubs.sort_options'),
            ],
        ]);
    }

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
