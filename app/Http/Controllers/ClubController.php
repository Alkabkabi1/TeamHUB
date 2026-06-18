<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Post;
use App\Models\VolunteerHour;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    /**
     * Show a single club page with stats and events.
     */
    public function show(Request $request, Club $club): Response
    {
        $club->loadCount([
            'memberships as members_count',
            'events as upcoming_events_count' => fn ($query) => $query
                ->where('starts_at', '>=', now())
                ->where('status', 'active'),
        ]);

        $volunteerHoursSum = (float) VolunteerHour::query()
            ->where('club_id', $club->id)
            ->sum('hours');

        $upcomingEvents = Event::query()
            ->where('club_id', $club->id)
            ->with('media')
            ->upcoming()
            ->active()
            ->orderBy('starts_at')
            ->limit(6)
            ->get(['id', 'title', 'description', 'starts_at']);

        $posts = Post::query()
            ->where('club_id', $club->id)
            ->with('media')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => mb_substr(strip_tags($post->body), 0, 160),
                'published_at' => $post->published_at->locale(app()->getLocale())->diffForHumans(),
                'image_url' => $post->coverImageUrl(),
            ])
            ->values();

        // Real events that power the club calendar. The frontend buckets these
        // by day and navigates between months entirely on the client.
        $calendarEvents = Event::query()
            ->where('club_id', $club->id)
            ->active()
            ->orderBy('starts_at')
            ->get(['id', 'title', 'starts_at'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at->toIso8601String(),
            ])
            ->values();

        // A preview of the club's committees for the "اللجان" section; the full
        // grid lives on the dedicated committees listing.
        $committees = $club->committees()
            ->withCount('memberships as members_count')
            ->with('media')
            ->where('status', 'active')
            ->orderByDesc('members_count')
            ->limit(4)
            ->get()
            ->map(fn (Committee $committee) => [
                'id' => $committee->id,
                'name' => $committee->name,
                'description' => mb_substr(strip_tags((string) $committee->description), 0, 160),
                'image_url' => $committee->logo_url ?: $committee->coverImageUrl(),
                'members_count' => $committee->members_count,
            ])
            ->values();

        $userId = $request->user()?->id;
        $isMember = $userId && (
            ClubMembership::query()
                ->where('user_id', $userId)
                ->where('club_id', $club->id)
                ->where('status', 'approved')
                ->exists()
            || ClubJoinApplication::query()
                ->where('user_id', $userId)
                ->where('club_id', $club->id)
                ->where('status', 'pending')
                ->exists()
        );

        return Inertia::render('ClubPage', [
            // Override the shared university brand with this club's color when set.
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url', 'category', 'college', 'status']),
            // Staff and club managers get a shortcut into the management dashboard.
            'canManage' => $request->user()?->canManageClub($club) ?? false,
            'isMember' => $isMember,
            'stats' => [
                'members_count' => $club->members_count,
                'upcoming_events_count' => $club->upcoming_events_count,
                'volunteer_hours_sum' => $volunteerHoursSum,
            ],
            'upcomingEvents' => $upcomingEvents->map(fn (Event $event) => $this->formatEventCard($event, $club->name))->values(),
            'posts' => $posts,
            'calendarEvents' => $calendarEvents,
            'committees' => $committees,
        ]);
    }

    /**
     * @return array{id: int, club: string, time: string, title: string, description: string, image_url: string|null}
     */
    private function formatEventCard(Event $event, string $clubName): array
    {
        return [
            'id' => $event->id,
            'club' => $clubName,
            'time' => $event->starts_at->locale(app()->getLocale())->diffForHumans(),
            'title' => $event->title,
            'description' => $event->description ?? '',
            'image_url' => $event->coverImageUrl(),
        ];
    }
}
