<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
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

        $clubsQuery = Club::query()
            ->with('media')
            ->withCount('memberships as members_count')
            ->when($hasSearch, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($hasCategory, fn ($query) => $query->where('category', $category))
            ->when($hasCollege, fn ($query) => $query->where('college', $college))
            ->when($hasExplicitStatus, fn ($query) => $query->where('status', $statusInput))
            ->when(! $hasExplicitStatus, fn ($query) => $query->where('status', 'active'));

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
