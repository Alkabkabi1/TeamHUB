<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Enums\UserRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClubMemberController extends Controller
{
    /**
     * Search registered users that can be added to the club, excluding those
     * who already have a membership. Used by the "add member" picker.
     */
    public function search(Request $request, Club $club): JsonResponse
    {
        $this->authorize(ClubCapability::ManageMembers->value, $club);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['users' => []]);
        }

        $existingUserIds = ClubMembership::query()
            ->where('club_id', $club->id)
            ->pluck('user_id');

        $users = User::query()
            ->where('role', UserRole::Student->value)
            ->whereNotIn('id', $existingUserIds)
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return response()->json(['users' => $users]);
    }

    /**
     * Add an existing user to the club as an approved member. Manager roles may
     * only be granted at creation by someone who can also manage the club
     * (ClubLead), preventing a membership manager from minting managers.
     */
    public function store(Request $request, Club $club): RedirectResponse
    {
        $this->authorize(ClubCapability::ManageMembers->value, $club);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Student->value)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->resolveRoles($validated['roles'] ?? []);

        if ($this->includesManagerRole($roles)) {
            abort_unless($request->user()->can(ClubCapability::ManageClub->value, $club), 403);
        }

        $membership = ClubMembership::updateOrCreate(
            ['user_id' => $validated['user_id'], 'club_id' => $club->id],
            [
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        // Every member always holds the baseline Member role.
        $membership->syncClubRoles($this->withMember($roles));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('members.added')]);

        return back();
    }

    /**
     * Replace a membership's roles. Restricted to club leads (manage-club),
     * who may promote/demote members within their own club.
     */
    public function updateRoles(Request $request, Club $club, ClubMembership $membership): RedirectResponse
    {
        $this->authorize(ClubCapability::ManageClub->value, $club);

        abort_unless($membership->club_id === $club->id, 404);

        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->withMember($this->resolveRoles($validated['roles']));

        // Protect the club's last lead: a club must always keep one ClubLead.
        if ($this->wouldRemoveLastLead($club, $membership, $roles)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('members.last_lead')]);

            return back();
        }

        $membership->syncClubRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('members.roles_updated')]);

        return back();
    }

    /**
     * Remove a member from the club.
     */
    public function destroy(Request $request, Club $club, ClubMembership $membership): RedirectResponse
    {
        $this->authorize(ClubCapability::ManageMembers->value, $club);

        abort_unless($membership->club_id === $club->id, 404);

        // Removing the last lead would leave the club leaderless.
        if ($this->wouldRemoveLastLead($club, $membership, [])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('members.last_lead')]);

            return back();
        }

        $membership->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('members.removed')]);

        return back();
    }

    /**
     * Whether applying $newRoles to $membership would leave the club with no
     * ClubLead at all.
     *
     * @param  array<int, ClubRole>  $newRoles
     */
    private function wouldRemoveLastLead(Club $club, ClubMembership $membership, array $newRoles): bool
    {
        $currentlyLead = $membership->hasClubRole(ClubRole::ClubLead);
        $remainsLead = in_array(ClubRole::ClubLead, $newRoles, true);

        if (! $currentlyLead || $remainsLead) {
            return false;
        }

        $otherLeads = ClubMembership::query()
            ->where('club_id', $club->id)
            ->whereKeyNot($membership->id)
            ->whereHas('roles', fn ($query) => $query->where('role', ClubRole::ClubLead->value))
            ->exists();

        return ! $otherLeads;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, ClubRole>
     */
    private function resolveRoles(array $values): array
    {
        return array_map(fn (string $value): ClubRole => ClubRole::from($value), array_values(array_unique($values)));
    }

    /**
     * Ensure the baseline Member role is always present in the set.
     *
     * @param  array<int, ClubRole>  $roles
     * @return array<int, ClubRole>
     */
    private function withMember(array $roles): array
    {
        if (! in_array(ClubRole::Member, $roles, true)) {
            $roles[] = ClubRole::Member;
        }

        return $roles;
    }

    /**
     * @param  array<int, ClubRole>  $roles
     */
    private function includesManagerRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role->isManager()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function roleValues(): array
    {
        return array_map(fn (ClubRole $role): string => $role->value, ClubRole::cases());
    }
}
