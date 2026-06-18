<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    /**
     * Show the public homepage with clubs and upcoming events.
     *
     * Single search (`search`) plus optional `category`, `college`, and `status`
     * query params. When any narrowing input is present, result caps widen (50/50).
     * Clubs default to `status = active` unless the client sends an explicit `status`.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $category = trim((string) $request->string('category'));
        $college = trim((string) $request->string('college'));
        $statusInput = $request->filled('status') ? trim((string) $request->string('status')) : null;

        $hasSearch = $search !== '';
        $hasCategory = $category !== '';
        $hasCollege = $college !== '';
        $hasExplicitStatus = $statusInput !== null && $statusInput !== '';

        $widenCaps = $hasSearch || $hasCategory || $hasCollege || $hasExplicitStatus;

        $clubLimit = $widenCaps ? 50 : 8;
        $eventLimit = $widenCaps ? 50 : 4;

        $clubsQuery = Club::query()
            ->with('media')
            ->withCount('memberships as members_count')
            ->when($hasSearch, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($hasCategory, fn ($query) => $query->where('category', $category))
            ->when($hasCollege, fn ($query) => $query->where('college', $college))
            ->when($hasExplicitStatus, fn ($query) => $query->where('status', $statusInput))
            ->when(! $hasExplicitStatus, fn ($query) => $query->where('status', 'active'));

        $eventsQuery = Event::query()
            ->with(['club:id,name,category,college,status', 'media'])
            ->where('starts_at', '>=', now())
            ->whereHas('club', function ($query) use ($hasCategory, $category, $hasCollege, $college, $hasExplicitStatus, $statusInput) {
                $query
                    ->when($hasCategory, fn ($q) => $q->where('category', $category))
                    ->when($hasCollege, fn ($q) => $q->where('college', $college))
                    ->when($hasExplicitStatus, fn ($q) => $q->where('status', $statusInput))
                    ->when(! $hasExplicitStatus, fn ($q) => $q->where('status', 'active'));
            })
            ->when($hasSearch, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('starts_at')
            ->limit($eventLimit);

        $clubs = $clubsQuery
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->limit($clubLimit)
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

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'clubs' => $clubs,
            'events' => $eventsQuery->get(['id', 'club_id', 'title', 'description', 'starts_at'])
                ->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'club' => $event->club?->only(['id', 'name']),
                    'image_url' => $event->coverImageUrl(),
                ]),
            'posts' => Post::query()
                ->with(['club:id,name', 'media'])
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(4)
                ->get(['id', 'title', 'body', 'published_at', 'club_id'])
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 160),
                    'published_at' => $post->published_at?->locale(app()->getLocale())->diffForHumans(),
                    'club' => $post->club?->name,
                    'image_url' => $post->coverImageUrl(),
                ]),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'college' => $college,
                'status' => $statusInput ?? '',
            ],
            'filterOptions' => [
                'categories' => Club::query()
                    ->whereNotNull('category')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
                    ->values(),
                'colleges' => Club::query()
                    ->whereNotNull('college')
                    ->distinct()
                    ->orderBy('college')
                    ->pluck('college')
                    ->values(),
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                    ['value' => 'founding', 'label' => 'تحت التأسيس'],
                ],
            ],
        ]);
    }
}
