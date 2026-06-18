<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommitteeMemberController extends Controller
{
    /**
     * Search approved members of the parent club that can be added to the
     * committee, excluding those who already have a committee membership.
     */
    public function search(Request $request, Club $club, Committee $committee): JsonResponse
    {
        $this->authorize(CommitteeCapability::ManageMembers->value, $committee);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['users' => []]);
        }

        $existingUserIds = CommitteeMembership::query()
            ->where('committee_id', $committee->id)
            ->pluck('user_id');

        // Eligible pool: approved members of the parent club only.
        $clubMemberIds = $club->memberships()
            ->where('status', 'approved')
            ->pluck('user_id');

        $users = User::query()
            ->whereIn('id', $clubMemberIds)
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
     * Add an approved club member to the committee. Manager roles may only be
     * granted by someone who can also manage the committee (ManageCommittee).
     */
    public function store(Request $request, Club $club, Committee $committee): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageMembers->value, $committee);

        $validated = $request->validate([
            'user_id' => [
                'required', 'integer',
                Rule::exists('club_memberships', 'user_id')
                    ->where('club_id', $club->id)
                    ->where('status', 'approved'),
            ],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ], [
            'user_id.exists' => __('committee_members.validation.not_club_member'),
        ]);

        $roles = $this->resolveRoles($validated['roles'] ?? []);

        if ($this->includesManagerRole($roles)) {
            abort_unless($request->user()->can(CommitteeCapability::ManageCommittee->value, $committee), 403);
        }

        $membership = CommitteeMembership::updateOrCreate(
            ['user_id' => $validated['user_id'], 'committee_id' => $committee->id],
            [
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncCommitteeRoles($this->withMember($roles));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.added')]);

        return back();
    }

    /**
     * Replace a membership's roles. Restricted to committee leads.
     */
    public function updateRoles(Request $request, Club $club, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageCommittee->value, $committee);

        abort_unless($membership->committee_id === $committee->id, 404);

        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->withMember($this->resolveRoles($validated['roles']));

        if ($this->wouldRemoveLastLead($committee, $membership, $roles)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('committee_members.last_lead')]);

            return back();
        }

        $membership->syncCommitteeRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.roles_updated')]);

        return back();
    }

    /**
     * Remove a member from the committee.
     */
    public function destroy(Request $request, Club $club, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        $this->authorize(CommitteeCapability::ManageMembers->value, $committee);

        abort_unless($membership->committee_id === $committee->id, 404);

        if ($this->wouldRemoveLastLead($committee, $membership, [])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('committee_members.last_lead')]);

            return back();
        }

        $membership->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('committee_members.removed')]);

        return back();
    }

    /**
     * Whether applying $newRoles to $membership would leave the committee with
     * no CommitteeLead at all.
     *
     * @param  array<int, CommitteeRole>  $newRoles
     */
    private function wouldRemoveLastLead(Committee $committee, CommitteeMembership $membership, array $newRoles): bool
    {
        $currentlyLead = $membership->hasCommitteeRole(CommitteeRole::CommitteeLead);
        $remainsLead = in_array(CommitteeRole::CommitteeLead, $newRoles, true);

        if (! $currentlyLead || $remainsLead) {
            return false;
        }

        $otherLeads = CommitteeMembership::query()
            ->where('committee_id', $committee->id)
            ->whereKeyNot($membership->id)
            ->whereHas('roles', fn ($query) => $query->where('role', CommitteeRole::CommitteeLead->value))
            ->exists();

        return ! $otherLeads;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, CommitteeRole>
     */
    private function resolveRoles(array $values): array
    {
        return array_map(fn (string $value): CommitteeRole => CommitteeRole::from($value), array_values(array_unique($values)));
    }

    /**
     * Ensure the baseline Member role is always present in the set.
     *
     * @param  array<int, CommitteeRole>  $roles
     * @return array<int, CommitteeRole>
     */
    private function withMember(array $roles): array
    {
        if (! in_array(CommitteeRole::Member, $roles, true)) {
            $roles[] = CommitteeRole::Member;
        }

        return $roles;
    }

    /**
     * @param  array<int, CommitteeRole>  $roles
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
        return array_map(fn (CommitteeRole $role): string => $role->value, CommitteeRole::cases());
    }
}
