<?php

namespace App\Http\Middleware;

use App\Models\Club;
use App\Models\Committee;
use App\Support\LoadsTranslations;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
            'translations' => LoadsTranslations::all($locale),
            'name' => config('app.name'),
            'theme' => [
                // University-level default brand color. Club-scoped pages may
                // override this key in their own Inertia::render() props.
                'brand' => config('theme.brand'),
            ],
            'auth' => [
                'user' => $request->user()
                    ? (function () use ($request) {
                        $user = $request->user();

                        // University staff can manage every club and committee;
                        // everyone else gets only the ones they hold a role in.
                        if ($user->isUniversityStaff()) {
                            $managedClubs = Club::all()
                                ->map(fn ($club) => [
                                    'id' => $club->id,
                                    'name' => $club->name,
                                    'logo_url' => $club->logo_url,
                                ])
                                ->values();

                            $managedCommittees = Committee::all()
                                ->map(fn ($committee) => [
                                    'id' => $committee->id,
                                    'name' => $committee->name,
                                    'club_id' => $committee->club_id,
                                ])
                                ->values();
                        } else {
                            $managedClubs = $user->managedClubs()
                                ->map(fn ($club) => [
                                    'id' => $club->id,
                                    'name' => $club->name,
                                    'logo_url' => $club->logo_url,
                                ])
                                ->values();

                            // Club leaders inherit management of every committee
                            // in their club, so merge those in with any committees
                            // the user leads directly via a committee role.
                            $clubIds = $managedClubs->pluck('id');
                            $inheritedCommittees = $clubIds->isNotEmpty()
                                ? Committee::whereIn('club_id', $clubIds)->get()
                                : collect();

                            $managedCommittees = $user->managedCommittees()
                                ->merge($inheritedCommittees)
                                ->unique('id')
                                ->map(fn ($committee) => [
                                    'id' => $committee->id,
                                    'name' => $committee->name,
                                    'club_id' => $committee->club_id,
                                ])
                                ->values();
                        }

                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role->value,
                            'managed_clubs' => $managedClubs,
                            'is_club_supervisor' => $managedClubs->isNotEmpty(),
                            'managed_committees' => $managedCommittees,
                            'is_committee_leader' => $managedCommittees->isNotEmpty(),
                        ];
                    })()
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
