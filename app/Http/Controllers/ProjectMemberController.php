<?php

namespace App\Http\Controllers;

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectMemberController extends Controller
{
    public function search(Request $request, Workspace $workspace, Project $project): JsonResponse
    {
        $this->authorize(ProjectCapability::ManageMembers->value, $project);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['users' => []]);
        }

        $existingUserIds = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->pluck('user_id');

        $workspaceMemberIds = $workspace->memberships()
            ->where('status', 'approved')
            ->pluck('user_id');

        $users = User::query()
            ->whereIn('id', $workspaceMemberIds)
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

    public function store(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageMembers->value, $project);

        $validated = $request->validate([
            'user_id' => [
                'required', 'integer',
                Rule::exists('workspace_memberships', 'user_id')
                    ->where('workspace_id', $workspace->id)
                    ->where('status', 'approved'),
            ],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ], [
            'user_id.exists' => __('project.members.validation.not_club_member'),
        ]);

        $roles = $this->resolveRoles($validated['roles'] ?? []);

        if ($this->includesManagerRole($roles)) {
            abort_unless($request->user()->can(ProjectCapability::ManageProject->value, $project), 403);
        }

        $membership = ProjectMembership::updateOrCreate(
            ['user_id' => $validated['user_id'], 'project_id' => $project->id],
            [
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->syncProjectRoles($this->withMember($roles));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.added')]);

        return back();
    }

    public function updateRoles(Request $request, Workspace $workspace, Project $project, ProjectMembership $membership): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageProject->value, $project);

        abort_unless($membership->project_id === $project->id, 404);

        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in($this->roleValues())],
        ]);

        $roles = $this->withMember($this->resolveRoles($validated['roles']));

        if ($this->wouldRemoveLastLead($project, $membership, $roles)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('project.members.last_lead')]);

            return back();
        }

        $membership->syncProjectRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.roles_updated')]);

        return back();
    }

    public function destroy(Request $request, Workspace $workspace, Project $project, ProjectMembership $membership): RedirectResponse
    {
        $this->authorize(ProjectCapability::ManageMembers->value, $project);

        abort_unless($membership->project_id === $project->id, 404);

        if ($this->wouldRemoveLastLead($project, $membership, [])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('project.members.last_lead')]);

            return back();
        }

        $membership->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('project.members.removed')]);

        return back();
    }

    /**
     * @param  array<int, ProjectRole>  $newRoles
     */
    private function wouldRemoveLastLead(Project $project, ProjectMembership $membership, array $newRoles): bool
    {
        $currentlyLead = $membership->hasProjectRole(ProjectRole::ProjectLead);
        $remainsLead = in_array(ProjectRole::ProjectLead, $newRoles, true);

        if (! $currentlyLead || $remainsLead) {
            return false;
        }

        $otherLeads = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->whereKeyNot($membership->id)
            ->whereHas('roles', fn ($query) => $query->where('role', ProjectRole::ProjectLead->value))
            ->exists();

        return ! $otherLeads;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, ProjectRole>
     */
    private function resolveRoles(array $values): array
    {
        return array_map(fn (string $value): ProjectRole => ProjectRole::from($value), array_values(array_unique($values)));
    }

    /**
     * @param  array<int, ProjectRole>  $roles
     * @return array<int, ProjectRole>
     */
    private function withMember(array $roles): array
    {
        if (! in_array(ProjectRole::Member, $roles, true)) {
            $roles[] = ProjectRole::Member;
        }

        return $roles;
    }

    /**
     * @param  array<int, ProjectRole>  $roles
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
        return array_map(fn (ProjectRole $role): string => $role->value, ProjectRole::cases());
    }
}
