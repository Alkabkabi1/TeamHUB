<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function show(Request $request, Club $club): Response
    {
        $projectIds = $club->committees()->pluck('id');

        $club->loadCount([
            'memberships as members_count',
            'committees as projects_count',
        ]);

        $openTasksCount = Task::query()
            ->whereIn('committee_id', $projectIds)
            ->whereNotIn('status', ['done'])
            ->count();

        $recentUpdates = Post::query()
            ->whereIn('committee_id', $projectIds)
            ->with('committee:id,name,club_id')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'published_at', 'committee_id', 'club_id'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => mb_substr(strip_tags((string) $post->body), 0, 160),
                'published_at' => $post->published_at?->locale(app()->getLocale())->diffForHumans(),
                'committee_name' => $post->committee?->name,
                'url' => $post->committee_id
                    ? route('committees.updates.index', [$club, $post->committee_id], absolute: false)
                    : null,
            ])
            ->values();

        $committees = $club->committees()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
            ])
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
                'tasks_count' => $committee->tasks_count,
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
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => $club->only(['id', 'name', 'theme', 'logo_url', 'category', 'college', 'status']),
            'canManage' => $request->user()?->canManageClub($club) ?? false,
            'isMember' => $isMember,
            'stats' => [
                'members_count' => $club->members_count,
                'projects_count' => $club->projects_count,
                'open_tasks_count' => $openTasksCount,
            ],
            'recentUpdates' => $recentUpdates,
            'committees' => $committees,
        ]);
    }
}
