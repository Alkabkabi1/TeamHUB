<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WorkspaceMemberController extends Controller
{
    /**
     * Search registered users that can be added to the club, excluding those
     * who already have a membership. Used by the "add member" picker.
     */
    public function search(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize(WorkspaceCapability::ManageMembers->value, $workspace);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['users' => []]);
        }

        $existingUserIds = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->pluck('user_id');

        $users = User::query()
            ->where('role', UserRole::Member->value)
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
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize(WorkspaceCapability::ManageMembers->value, $workspace);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Member->value)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->resolveRoles($validated['roles'] ?? []);

        if ($this->includesManagerRole($roles)) {
            abort_unless($request->user()->can(WorkspaceCapability::ManageWorkspace->value, $workspace), 403);
        }

        $membership = WorkspaceMembership::updateOrCreate(
            ['user_id' => $validated['user_id'], 'workspace_id' => $workspace->id],
            [
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        // Every member always holds the baseline Member role.
        $membership->syncWorkspaceRoles($this->withMember($roles));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('members.added')]);

        return back();
    }

    /**
     * Replace a membership's roles. Restricted to club leads (manage-club),
     * who may promote/demote members within their own club.
     */
    public function updateRoles(Request $request, Workspace $workspace, WorkspaceMembership $membership): RedirectResponse
    {
        $this->authorize(WorkspaceCapability::ManageWorkspace->value, $workspace);

        abort_unless($membership->workspace_id === $workspace->id, 404);

        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->withMember($this->resolveRoles($validated['roles']));

        // Protect the club's last lead: a club must always keep one ClubLead.
        if ($this->wouldRemoveLastLead($workspace, $membership, $roles)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('members.last_lead')]);

            return back();
        }

        $membership->syncWorkspaceRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('members.roles_updated')]);

        return back();
    }

    /**
     * Remove a member from the club.
     */
    public function destroy(Request $request, Workspace $workspace, WorkspaceMembership $membership): RedirectResponse
    {
        $this->authorize(WorkspaceCapability::ManageMembers->value, $workspace);

        abort_unless($membership->workspace_id === $workspace->id, 404);

        // Removing the last lead would leave the club leaderless.
        if ($this->wouldRemoveLastLead($workspace, $membership, [])) {
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
     * @param  array<int, WorkspaceRole>  $newRoles
     */
    private function wouldRemoveLastLead(Workspace $workspace, WorkspaceMembership $membership, array $newRoles): bool
    {
        $currentlyLead = $membership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead);
        $remainsLead = in_array(WorkspaceRole::WorkspaceLead, $newRoles, true);

        if (! $currentlyLead || $remainsLead) {
            return false;
        }

        $otherLeads = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->whereKeyNot($membership->id)
            ->whereHas('roles', fn ($query) => $query->where('role', WorkspaceRole::WorkspaceLead->value))
            ->exists();

        return ! $otherLeads;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, WorkspaceRole>
     */
    private function resolveRoles(array $values): array
    {
        return array_map(fn (string $value): WorkspaceRole => WorkspaceRole::from($value), array_values(array_unique($values)));
    }

    /**
     * Ensure the baseline Member role is always present in the set.
     *
     * @param  array<int, WorkspaceRole>  $roles
     * @return array<int, WorkspaceRole>
     */
    private function withMember(array $roles): array
    {
        if (! in_array(WorkspaceRole::Member, $roles, true)) {
            $roles[] = WorkspaceRole::Member;
        }

        return $roles;
    }

    /**
     * @param  array<int, WorkspaceRole>  $roles
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
        return array_map(fn (WorkspaceRole $role): string => $role->value, WorkspaceRole::cases());
    }
}
