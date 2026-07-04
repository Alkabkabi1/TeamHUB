<?php

namespace App\Http\Controllers\TeamHub;

use App\Enums\CommitteeRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;
use App\Support\DemoRoles;
use App\Support\DemoWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamHubDemoController extends Controller
{
    public function storeProject(Request $request): RedirectResponse
    {
        $this->assertPersona($request, 'admin');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'leader_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $club = ! empty($validated['club_id'])
            ? Club::query()->findOrFail($validated['club_id'])
            : DemoWorkspace::defaultClub();

        $committee = $club->committees()->create([
            'name' => $validated['name'],
            'description' => null,
            'status' => 'active',
        ]);

        if (! empty($validated['leader_id'])) {
            $leader = User::query()->findOrFail($validated['leader_id']);

            $membership = CommitteeMembership::query()->firstOrCreate(
                ['user_id' => $leader->id, 'committee_id' => $committee->id],
                [
                    'status' => 'approved',
                    'requested_at' => now(),
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'joined_at' => now(),
                ],
            );

            $membership->assignCommitteeRole(CommitteeRole::Member);
            $membership->assignCommitteeRole(CommitteeRole::CommitteeLead);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('hub.admin.project_created'),
        ]);

        return redirect()->route('hub.dashboard');
    }

    public function assignLeader(Request $request): RedirectResponse
    {
        $this->assertPersona($request, 'admin');

        $validated = $request->validate([
            'committee_id' => ['required', 'integer', 'exists:committees,id'],
            'leader_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $committee = Committee::query()->findOrFail($validated['committee_id']);
        $leader = User::query()->findOrFail($validated['leader_id']);

        abort_unless(
            (DemoRoles::find($leader->email)['role'] ?? null) === 'project_leader',
            422,
        );

        $membership = CommitteeMembership::query()->firstOrCreate(
            ['user_id' => $leader->id, 'committee_id' => $committee->id],
            [
                'status' => 'approved',
                'requested_at' => now(),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        $membership->assignCommitteeRole(CommitteeRole::Member);
        $membership->assignCommitteeRole(CommitteeRole::CommitteeLead);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('hub.admin.leader_assigned'),
        ]);

        return redirect()->route('hub.dashboard');
    }

    public function messageLeader(Request $request): RedirectResponse
    {
        $this->assertPersona($request, 'admin');

        $request->validate([
            'leader_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('hub.admin.message_sent'),
        ]);

        return redirect()->route('hub.dashboard');
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $this->assertPersona($request, 'project_leader');

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'committee_id' => ['required', 'integer', 'exists:committees,id'],
            'title' => ['required', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(TaskPriority::values())],
        ]);

        $committee = Committee::query()->findOrFail($validated['committee_id']);
        abort_unless($user->canManageCommittee($committee), 403);

        Task::query()->create([
            'committee_id' => $committee->id,
            'created_by' => $user->id,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'title' => $validated['title'],
            'description' => null,
            'status' => TaskStatus::Todo,
            'priority' => $validated['priority'] ?? TaskPriority::Medium->value,
            'due_at' => $validated['due_at'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('hub.leader.task_created'),
        ]);

        return redirect()->route('hub.dashboard');
    }

    public function submitDeliverable(Request $request, Task $task): RedirectResponse
    {
        $this->assertPersona($request, 'staff');

        /** @var User $user */
        $user = $request->user();

        abort_unless($task->isAssignedTo($user), 403);

        $validated = $request->validate([
            'deliverable_url' => ['nullable', 'url', 'max:2048'],
            'deliverable_notes' => ['nullable', 'string', 'max:2000'],
            'deliverable_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $task->submitDeliverable(
            $user,
            $validated['deliverable_url'] ?? null,
            $validated['deliverable_notes'] ?? null,
            $request->hasFile('deliverable_file'),
        );

        if ($request->hasFile('deliverable_file')) {
            $task->addMedia($request->file('deliverable_file'))
                ->toMediaCollection(Task::DELIVERABLE_COLLECTION);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tasks.deliverable_submitted'),
        ]);

        return redirect()->route('hub.dashboard');
    }

    private function assertPersona(Request $request, string $persona): void
    {
        abort_unless((bool) config('demo.quick_login'), 404);

        $account = DemoRoles::find($request->user()?->email ?? '');

        abort_unless(($account['role'] ?? null) === $persona, 403);
    }
}
