<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TaskDeliverablePanel from '@/components/tasks/TaskDeliverablePanel.svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskReviewPanel from '@/components/tasks/TaskReviewPanel.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';

    type TaskDetail = {
        id: number;
        title: string;
        description: string | null;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high' | 'urgent';
        due_at: string | null;
        assignee: { id: number; name: string } | null;
        creator: { id: number; name: string } | null;
        reviewer: { id: number; name: string } | null;
        deliverable_url: string | null;
        deliverable_notes: string | null;
        deliverable_file: { name: string; url: string } | null;
        review_notes: string | null;
        has_deliverable: boolean;
    };

    let {
        task,
        canSubmitDeliverable = false,
        canApproveDeliverable = false,
        approveUrl = '',
        requestChangesUrl = '',
        submitDeliverableUrl = '',
        tasksIndexUrl = '/tasks',
    }: {
        task: TaskDetail;
        canSubmitDeliverable?: boolean;
        canApproveDeliverable?: boolean;
        approveUrl?: string;
        requestChangesUrl?: string;
        submitDeliverableUrl?: string;
        tasksIndexUrl?: string;
    } = $props();

    const deliverableForm = useForm({
        deliverable_url: '',
        deliverable_notes: '',
        deliverable_file: null as File | null,
    });

    const reviewForm = useForm({
        review_notes: '',
    });

    function onDeliverableFileChange(event: Event): void {
        const file =
            (event.currentTarget as HTMLInputElement).files?.[0] ?? null;
        deliverableForm.deliverable_file = file;
    }

    function submitDeliverable(event: SubmitEvent): void {
        event.preventDefault();
        deliverableForm.post(submitDeliverableUrl, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    function approveReview(): void {
        reviewForm.post(approveUrl, { preserveScroll: true });
    }

    function requestChanges(): void {
        reviewForm.post(requestChangesUrl, { preserveScroll: true });
    }
</script>

<AppHead title={task.title} />

<div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mb-6">
        <Link
            href={tasksIndexUrl}
            class="text-sm font-medium"
            style="color: var(--th-primary)"
        >
            ← {t('dashboard.nav.tasks')}
        </Link>
    </div>

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="th-card rounded-2xl p-6">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold" style="color: var(--th-text)">
                    {task.title}
                </h1>
                <TaskStatusBadge status={task.status} variant="shell" />
                <TaskPriorityBadge priority={task.priority} />
            </div>

            {#if task.description}
                <p
                    class="mt-3 text-sm leading-relaxed"
                    style="color: var(--th-text-muted)"
                >
                    {task.description}
                </p>
            {/if}

            <dl
                class="mt-4 grid gap-3 text-sm sm:grid-cols-2"
                style="color: var(--th-text-muted)"
            >
                {#if task.assignee}
                    <div>
                        <dt>{t('tasks.assignee')}</dt>
                        <dd style="color: var(--th-text)">
                            {task.assignee.name}
                        </dd>
                    </div>
                {/if}
                {#if task.due_at}
                    <div>
                        <dt>{t('tasks.due_date')}</dt>
                        <dd style="color: var(--th-text)">
                            {formatDate(task.due_at)}
                        </dd>
                    </div>
                {/if}
                {#if task.creator}
                    <div>
                        <dt>{t('tasks.created_by')}</dt>
                        <dd style="color: var(--th-text)">
                            {task.creator.name}
                        </dd>
                    </div>
                {/if}
            </dl>
        </div>

        <TaskDeliverablePanel
            {task}
            {canSubmitDeliverable}
            {deliverableForm}
            {onDeliverableFileChange}
            onSubmit={submitDeliverable}
            onBlurEscape={() => {}}
        />

        {#if canApproveDeliverable && task.status === 'review'}
            <TaskReviewPanel
                {reviewForm}
                onApprove={approveReview}
                onRequestChanges={requestChanges}
                onBlurEscape={() => {}}
            />
        {/if}
    </div>
</div>
