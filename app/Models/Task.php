<?php

namespace App\Models;

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Enums\TaskActivityType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskChangesRequestedNotification;
use App\Notifications\TaskDeliverableApprovedNotification;
use App\Notifications\TaskSubmittedForReviewNotification;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements HasMedia
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const string DELIVERABLE_COLLECTION = 'deliverable';

    protected $fillable = [
        'committee_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'deliverable_url',
        'deliverable_notes',
        'submitted_for_review_at',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
            'submitted_for_review_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DELIVERABLE_COLLECTION)->singleFile();
    }

    /**
     * @return BelongsTo<Committee, $this>
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * @return HasMany<TaskActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function scopeForCommittee(Builder $query, Committee $committee): Builder
    {
        return $query->where('committee_id', $committee->id);
    }

    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where('assigned_to', $user->id);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Done->value);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->incomplete()
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', today());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->incomplete()
            ->whereDate('due_at', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->incomplete()
            ->whereNotNull('due_at')
            ->where('due_at', '>', now()->endOfDay());
    }

    public function scopeWithoutDueDate(Builder $query): Builder
    {
        return $query->incomplete()
            ->whereNull('due_at');
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to === $user->id;
    }

    public function submitDeliverable(User $actor, ?string $url, ?string $notes, bool $hasFile = false): void
    {
        $this->forceFill([
            'deliverable_url' => $url,
            'deliverable_notes' => $notes,
            'status' => TaskStatus::Review,
            'submitted_for_review_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'completed_at' => null,
            'review_notes' => null,
        ])->save();

        $this->recordActivity(TaskActivityType::DeliverableSubmitted, $actor, [
            'has_file' => $hasFile,
            'has_url' => filled($url),
            'has_notes' => filled($notes),
        ]);

        $this->notifyUsers(
            $this->managerRecipients(),
            new TaskSubmittedForReviewNotification($this, $actor),
            $actor,
        );
    }

    public function approve(User $reviewer, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => TaskStatus::Done,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => now(),
            'review_notes' => $notes,
        ])->save();

        $this->recordActivity(TaskActivityType::DeliverableApproved, $reviewer, [
            'review_notes' => $notes,
        ]);

        $this->notifyUsers(
            collect([$this->assignee])->filter(),
            new TaskDeliverableApprovedNotification($this, $reviewer),
            $reviewer,
        );
    }

    public function requestChanges(User $reviewer, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => TaskStatus::InProgress,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => null,
            'review_notes' => $notes,
        ])->save();

        $this->recordActivity(TaskActivityType::ChangesRequested, $reviewer, [
            'review_notes' => $notes,
        ]);

        $this->notifyUsers(
            collect([$this->assignee])->filter(),
            new TaskChangesRequestedNotification($this, $reviewer),
            $reviewer,
        );
    }

    public function addComment(User $author, string $body): TaskComment
    {
        $comment = $this->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        $this->recordActivity(TaskActivityType::CommentAdded, $author, [
            'comment_excerpt' => Str::limit($body, 140),
        ]);

        return $comment;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordActivity(TaskActivityType $type, User $actor, array $meta = []): TaskActivity
    {
        return $this->activities()->create([
            'user_id' => $actor->id,
            'type' => $type,
            'meta' => $meta,
        ]);
    }

    public function recordCreated(User $actor): void
    {
        $this->recordActivity(TaskActivityType::TaskCreated, $actor);
    }

    public function recordStatusChange(User $actor, TaskStatus $from, TaskStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $this->recordActivity(TaskActivityType::TaskStatusChanged, $actor, [
            'from_status' => $from->value,
            'to_status' => $to->value,
        ]);
    }

    public function recordAssignment(User $actor, ?User $fromAssignee, ?User $toAssignee): void
    {
        if ($fromAssignee?->id === $toAssignee?->id) {
            return;
        }

        $this->recordActivity(TaskActivityType::TaskAssigned, $actor, [
            'from_assignee_name' => $fromAssignee?->name,
            'to_assignee_name' => $toAssignee?->name,
        ]);

        if ($toAssignee !== null) {
            $this->notifyUsers(
                collect([$toAssignee]),
                new TaskAssignedNotification($this, $actor),
                $actor,
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function managerRecipients(): Collection
    {
        $this->loadMissing('committee.club');

        $committeeManagers = $this->committee->memberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', CommitteeRole::managerRoleValues()))
            ->with('user:id,name,email,locale')
            ->get()
            ->pluck('user')
            ->filter();

        $clubManagers = $this->committee->club->memberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', ClubRole::managerRoleValues()))
            ->with('user:id,name,email,locale')
            ->get()
            ->pluck('user')
            ->filter();

        return $committeeManagers
            ->merge($clubManagers)
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, mixed>  $users
     */
    private function notifyUsers(Collection $users, object $notification, ?User $except = null): void
    {
        $users
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->filter(fn (User $user): bool => $except === null || $user->id !== $except->id)
            ->each(fn (User $user): User => tap($user)->notify($notification));
    }
}
