<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\User;

function taskCommentLeadAndCommittee(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $clubMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $clubMembership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club, $committee];
}

function approvedTaskCommentMember(Club $club, Committee $committee, array $roles = [CommitteeRole::Member]): User
{
    $user = User::factory()->student()->create();

    ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);

    $membership = CommitteeMembership::factory()->create([
        'user_id' => $user->id,
        'committee_id' => $committee->id,
    ]);
    $membership->syncCommitteeRoles($roles);

    return $user;
}

test('project members can add comments and comments appear in task activity', function () {
    [$lead, $club, $committee] = taskCommentLeadAndCommittee();
    $member = approvedTaskCommentMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);

    $this->actingAs($member)
        ->post(route('committees.tasks.comments.store', [$club, $committee, $task]), [
            'body' => 'I uploaded the first draft and would like feedback.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    expect(TaskComment::query()->where('task_id', $task->id)->count())->toBe(1)
        ->and(TaskActivity::query()->where('task_id', $task->id)->where('type', 'comment.added')->exists())->toBeTrue();
});

test('authors and project leads can delete task comments, but other members cannot', function () {
    [$lead, $club, $committee] = taskCommentLeadAndCommittee();
    $author = approvedTaskCommentMember($club, $committee);
    $otherMember = approvedTaskCommentMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $author->id,
    ]);

    $comment = $task->addComment($author, 'Draft delivered.');

    $this->actingAs($otherMember)
        ->delete(route('committees.tasks.comments.destroy', [$club, $committee, $task, $comment]))
        ->assertForbidden();

    $this->actingAs($lead)
        ->delete(route('committees.tasks.comments.destroy', [$club, $committee, $task, $comment]))
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
});
