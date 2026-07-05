<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import TaskCommentsPanel from '@/components/tasks/TaskCommentsPanel.svelte';
    import TaskDeliverablePanel from '@/components/tasks/TaskDeliverablePanel.svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskReviewPanel from '@/components/tasks/TaskReviewPanel.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';

    type ClubRef = { id: number; name: string };
    type CommitteeRef = { id: number; name: string };
    type MemberOption = { value: number; label: string };
    type SelectOption = { value: string; label: string };
    type CommentItem = {
        id: number;
        body: string;
        author_name: string;
        created_at: string | null;
        can_delete: boolean;
        delete_url: string;
    };
    type ActivityItem = {
        id: number;
        type: string;
        message: string;
        created_at: string | null;
        task: { id: number; title: string; url: string };
    };
    type TaskDetail = {
        id: number;
        title: string;
        description: string | null;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high' | 'urgent';
        due_at: string | null;
        assignee_id: number | null;
        assignee: { id: number; name: string } | null;
        creator: { id: number; name: string } | null;
        reviewer: { id: number; name: string } | null;
        deliverable_url: string | null;
        deliverable_notes: string | null;
        deliverable_file: { name: string; url: string } | null;
        submitted_for_review_at: string | null;
        reviewed_at: string | null;
        completed_at: string | null;
        review_notes: string | null;
        has_deliverable: boolean;
    };

    let {
        club,
        committee,
        task,
        comments = [],
        activities = [],
        members = [],
        priorityOptions = [],
        canManageTasks = false,
        canSubmitDeliverable = false,
        canApproveDeliverable = false,
        canUpdateProgress = false,
        canComment = false,
        indexUrl,
        manageUrl,
    }: {
        club: ClubRef;
        committee: CommitteeRef;
        task: TaskDetail;
        comments?: CommentItem[];
        activities?: ActivityItem[];
        members?: MemberOption[];
        priorityOptions?: SelectOption[];
        canManageTasks?: boolean;
        canSubmitDeliverable?: boolean;
        canApproveDeliverable?: boolean;
        canUpdateProgress?: boolean;
        canComment?: boolean;
        indexUrl: string;
        manageUrl: string;
    } = $props();

    const taskUrl = `/workspaces/${club.id}/projects/${committee.id}/tasks/${task.id}`;

    const updateForm = useForm({
        title: task.title,
        description: task.description ?? '',
        assigned_to: task.assignee_id ?? '',
        priority: task.priority,
        due_at: task.due_at ? task.due_at.slice(0, 16) : '',
    });

    const progressForm = useForm({
        status: task.status === 'todo' ? 'in_progress' : 'todo',
    });

    const deliverableForm = useForm({
        deliverable_file: null as File | null,
        deliverable_url: task.deliverable_url ?? '',
        deliverable_notes: task.deliverable_notes ?? '',
    });

    const reviewForm = useForm({
        review_notes: task.review_notes ?? '',
    });

    const commentForm = useForm({
        body: '',
    });

    let selectedFileName = $state(task.deliverable_file?.name ?? '');

    function submitMetadata(event: SubmitEvent): void {
        event.preventDefault();
        updateForm.patch(taskUrl, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    function updateProgress(status: 'todo' | 'in_progress'): void {
        progressForm.status = status;
        progressForm.patch(taskUrl, {
            preserveScroll: true,
        });
    }

    function onDeliverableFileChange(event: Event): void {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        deliverableForm.deliverable_file = file;
        selectedFileName = file?.name ?? '';
    }

    function submitDeliverable(event: SubmitEvent): void {
        event.preventDefault();
        deliverableForm.post(`${taskUrl}/deliverable`, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    function approveTask(): void {
        reviewForm.post(`${taskUrl}/approve`, {
            preserveScroll: true,
        });
    }

    function requestChanges(): void {
        reviewForm.post(`${taskUrl}/request-changes`, {
            preserveScroll: true,
        });
    }

    function deleteTask(): void {
        if (!confirm(t('tasks.delete_confirm'))) {
            return;
        }

        router.delete(taskUrl, {
            preserveScroll: true,
        });
    }

    function submitComment(event: SubmitEvent): void {
        event.preventDefault();
        postComment();
    }

    function postComment(): void {
        if (commentForm.processing || commentForm.body.trim() === '') {
            return;
        }

        commentForm.post(`${taskUrl}/comments`, {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    }

    function blurOnEscape(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            (event.currentTarget as HTMLElement).blur();
        }
    }

    function handleCommentKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            (event.currentTarget as HTMLElement).blur();

            return;
        }

        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            postComment();
        }
    }

    function deleteComment(deleteUrl: string): void {
        router.delete(deleteUrl, {
            preserveScroll: true,
        });
    }
</script>

<AppHead title={`${task.title} — ${committee.name}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd] dark:bg-[#0f172a]">
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <ProjectManageShell active="tasks" {club} {committee} />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-2">
                <div
                    class="flex flex-wrap items-center gap-2 text-sm text-[#7e7e7e] dark:text-[#94a3b8]"
                >
                    <Link
                        href={manageUrl}
                        class="transition-colors hover:text-brand"
                        >{committee.name}</Link
                    >
                    <span>/</span>
                    <Link
                        href={indexUrl}
                        class="transition-colors hover:text-brand"
                        >{t('tasks.title')}</Link
                    >
                </div>

                <h1 class="text-2xl font-semibold text-black dark:text-white">
                    {task.title}
                </h1>
                <div class="flex flex-wrap items-center gap-2">
                    <TaskStatusBadge status={task.status} />
                    <TaskPriorityBadge priority={task.priority} />
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href={indexUrl}
                    class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                >
                    {t('tasks.back_to_tasks')}
                </Link>
                {#if canManageTasks}
                    <button
                        type="button"
                        onclick={deleteTask}
                        class="rounded-full bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition-colors hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-300 dark:hover:bg-rose-500/25"
                    >
                        {t('tasks.delete')}
                    </button>
                {/if}
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <div class="space-y-6">
                {#if canManageTasks}
                    <form
                        onsubmit={submitMetadata}
                        class="grid gap-4 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)] lg:grid-cols-2"
                    >
                        <div class="lg:col-span-2">
                            <h2
                                class="text-lg font-medium text-black dark:text-white"
                            >
                                {t('tasks.metadata')}
                            </h2>
                        </div>

                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label
                                for="title"
                                class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                            >
                                >{t('tasks.title')}</label
                            >
                            <input
                                id="title"
                                name="title"
                                bind:value={updateForm.title}
                                class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                            />
                            <InputError message={updateForm.errors.title} />
                        </div>

                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label
                                for="description"
                                class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                                >{t('tasks.description')}</label
                            >
                            <textarea
                                id="description"
                                name="description"
                                bind:value={updateForm.description}
                                rows="5"
                                class="rounded-[10px] border border-black/15 bg-white px-4 py-3 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                                onkeydown={blurOnEscape}
                            ></textarea>
                            <InputError
                                message={updateForm.errors.description}
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                for="assigned_to"
                                class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                                >{t('tasks.assignee')}</label
                            >
                            <select
                                id="assigned_to"
                                name="assigned_to"
                                bind:value={updateForm.assigned_to}
                                class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                            >
                                <option value="">{t('tasks.unassigned')}</option
                                >
                                {#each members as member (member.value)}
                                    <option value={member.value}
                                        >{member.label}</option
                                    >
                                {/each}
                            </select>
                            <InputError
                                message={updateForm.errors.assigned_to}
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                for="priority"
                                class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                            >
                                >{t('tasks.priority')}</label
                            >
                            <select
                                id="priority"
                                name="priority"
                                bind:value={updateForm.priority}
                                class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                            >
                                {#each priorityOptions as option (option.value)}
                                    <option value={option.value}
                                        >{option.label}</option
                                    >
                                {/each}
                            </select>
                            <InputError message={updateForm.errors.priority} />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                for="due_at"
                                class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                            >
                                >{t('tasks.due_date')}</label
                            >
                            <input
                                id="due_at"
                                name="due_at"
                                type="datetime-local"
                                bind:value={updateForm.due_at}
                                class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                            />
                            <InputError message={updateForm.errors.due_at} />
                        </div>

                        <div class="flex items-end">
                            <button
                                type="submit"
                                disabled={updateForm.processing}
                                class="rounded-full bg-brand px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
                            >
                                {t('tasks.save')}
                            </button>
                        </div>
                    </form>
                {:else if canUpdateProgress}
                    <div
                        class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
                    >
                        <h2
                            class="text-lg font-medium text-black dark:text-white"
                        >
                            {t('tasks.status')}
                        </h2>
                        <p
                            class="mt-1 text-sm text-[#7e7e7e] dark:text-[#94a3b8]"
                        >
                            {t('tasks.subtitle')}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                onclick={() => updateProgress('in_progress')}
                                disabled={task.status === 'in_progress' ||
                                    progressForm.processing}
                                class="rounded-full bg-brand px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
                            >
                                {t('tasks.start_work')}
                            </button>
                            <button
                                type="button"
                                onclick={() => updateProgress('todo')}
                                disabled={task.status === 'todo' ||
                                    progressForm.processing}
                                class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20 disabled:opacity-60"
                            >
                                {t('tasks.mark_todo')}
                            </button>
                        </div>
                    </div>
                {/if}

                <TaskDeliverablePanel
                    {task}
                    {canSubmitDeliverable}
                    {deliverableForm}
                    {selectedFileName}
                    {onDeliverableFileChange}
                    onSubmit={submitDeliverable}
                    onBlurEscape={blurOnEscape}
                />

                {#if canApproveDeliverable && task.status === 'review'}
                    <TaskReviewPanel
                        {reviewForm}
                        onApprove={approveTask}
                        onRequestChanges={requestChanges}
                        onBlurEscape={blurOnEscape}
                    />
                {/if}

                <TaskCommentsPanel
                    {comments}
                    {canComment}
                    {commentForm}
                    onSubmit={submitComment}
                    onCommentKeydown={handleCommentKeydown}
                    onDeleteComment={deleteComment}
                />
            </div>

            <aside class="space-y-4">
                <div
                    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
                >
                    <h2 class="text-lg font-medium text-black dark:text-white">
                        {t('tasks.details')}
                    </h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-[#7e7e7e] dark:text-[#94a3b8]">
                                {t('tasks.assignee')}
                            </dt>
                            <dd class="text-black dark:text-white">
                                {task.assignee?.name ?? t('tasks.unassigned')}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[#7e7e7e] dark:text-[#94a3b8]">
                                {t('tasks.created_by')}
                            </dt>
                            <dd class="text-black dark:text-white">
                                {task.creator?.name ?? t('tasks.not_set')}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[#7e7e7e] dark:text-[#94a3b8]">
                                {t('tasks.due_date')}
                            </dt>
                            <dd class="text-black dark:text-white">
                                {task.due_at
                                    ? formatDate(task.due_at, {
                                          year: 'numeric',
                                          month: 'short',
                                          day: 'numeric',
                                          hour: '2-digit',
                                          minute: '2-digit',
                                      })
                                    : t('tasks.not_set')}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[#7e7e7e] dark:text-[#94a3b8]">
                                {t('tasks.status')}
                            </dt>
                            <dd><TaskStatusBadge status={task.status} /></dd>
                        </div>
                        <div>
                            <dt class="text-[#7e7e7e] dark:text-[#94a3b8]">
                                {t('tasks.priority')}
                            </dt>
                            <dd>
                                <TaskPriorityBadge priority={task.priority} />
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
                >
                    <h2 class="text-lg font-medium text-black dark:text-white">
                        {t('tasks.review_panel')}
                    </h2>
                    <div
                        class="mt-4 space-y-3 text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        {#if task.submitted_for_review_at}
                            <p>
                                <span
                                    class="font-medium text-black dark:text-white"
                                    >{t('tasks.submit_for_review')}:</span
                                >
                                {formatDate(task.submitted_for_review_at, {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })}
                            </p>
                        {/if}

                        {#if task.reviewed_at}
                            <p>
                                <span
                                    class="font-medium text-black dark:text-white"
                                    >{t('tasks.review_notes')}:</span
                                >
                                {task.review_notes ?? t('tasks.not_set')}
                            </p>
                            <p>
                                <span
                                    class="font-medium text-black dark:text-white"
                                    >{t('tasks.status')}:</span
                                >
                                {task.reviewer?.name ?? t('tasks.not_set')}
                            </p>
                        {/if}

                        {#if task.completed_at}
                            <p>
                                <span
                                    class="font-medium text-black dark:text-white"
                                    >{t('tasks.approve')}:</span
                                >
                                {formatDate(task.completed_at, {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })}
                            </p>
                        {/if}
                    </div>
                </div>

                <div
                    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
                >
                    <h2 class="text-lg font-medium text-black dark:text-white">
                        {t('tasks.activity_title')}
                    </h2>

                    <div class="mt-4 space-y-3">
                        {#if activities.length === 0}
                            <p
                                class="text-sm text-[#7e7e7e] dark:text-[#94a3b8]"
                            >
                                {t('tasks.activity_empty')}
                            </p>
                        {:else}
                            {#each activities as activity (activity.id)}
                                <div
                                    class="rounded-[14px] border border-black/10 p-4 text-start dark:border-white/10"
                                >
                                    <p
                                        class="text-sm text-black dark:text-white"
                                    >
                                        {activity.message}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-[#9a9a9a] dark:text-[#94a3b8]"
                                    >
                                        {activity.created_at
                                            ? formatDate(activity.created_at, {
                                                  year: 'numeric',
                                                  month: 'short',
                                                  day: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : ''}
                                    </p>
                                </div>
                            {/each}
                        {/if}
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
