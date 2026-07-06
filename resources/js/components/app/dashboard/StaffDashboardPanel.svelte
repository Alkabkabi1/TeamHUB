<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { t } from '@/lib/i18n.svelte';

    type StaffTask = {
        id: number;
        title: string;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high' | 'urgent';
        due_label: string;
        project: string;
        has_deliverable: boolean;
        deliverable_url: string | null;
        deliverable_notes: string | null;
        can_submit: boolean;
        detail_url: string;
        submit_url: string;
    };

    let {
        tasks = [],
        stats = { open: 0, due_today: 0, in_review: 0 },
    }: {
        tasks?: StaffTask[];
        stats?: { open: number; due_today: number; in_review: number };
    } = $props();

    let activeTaskId = $state<number | null>(null);

    const deliverableForm = useForm({
        deliverable_url: '',
        deliverable_notes: '',
        deliverable_file: null as File | null,
    });

    function openUpload(taskId: number): void {
        activeTaskId = taskId;
        deliverableForm.reset();
    }

    function submitDeliverable(task: StaffTask): void {
        deliverableForm.post(task.submit_url, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                activeTaskId = null;
                deliverableForm.reset();
            },
        });
    }
</script>

<section class="space-y-6">
    <div>
        <h2 class="text-lg font-bold" style="color: var(--th-text)">
            {t('dashboard.staff.title')}
        </h2>
        <p class="text-sm" style="color: var(--th-text-muted)">
            {t('dashboard.staff.subtitle')}
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        {#each [{ value: stats.open, label: t('dashboard.staff.open_tasks') }, { value: stats.due_today, label: t('dashboard.staff.due_today') }, { value: stats.in_review, label: t('dashboard.staff.in_review') }] as stat (stat.label)}
            <div class="th-card rounded-2xl p-4">
                <p class="text-2xl font-bold" style="color: var(--th-text)">
                    {stat.value}
                </p>
                <p class="text-xs" style="color: var(--th-text-muted)">
                    {stat.label}
                </p>
            </div>
        {/each}
    </div>

    <div class="space-y-4">
        {#each tasks as task (task.id)}
            <div class="th-card rounded-2xl p-4">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="font-medium"
                                style="color: var(--th-text)"
                            >
                                {task.title}
                            </p>
                            <TaskStatusBadge status={task.status} />
                            <TaskPriorityBadge priority={task.priority} />
                        </div>
                        <p
                            class="mt-1 text-xs"
                            style="color: var(--th-text-muted)"
                        >
                            {task.project} · {task.due_label}
                        </p>
                        {#if task.has_deliverable && task.status === 'review'}
                            <p
                                class="mt-2 text-xs"
                                style="color: var(--th-success)"
                            >
                                {t('dashboard.staff.submitted')}
                            </p>
                        {/if}
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <Link
                            href={task.detail_url}
                            class="rounded-xl border px-3 py-2 text-xs"
                            style="border-color: var(--th-border); color: var(--th-text)"
                        >
                            {t('dashboard.view_all')}
                        </Link>
                        {#if task.can_submit}
                            <button
                                type="button"
                                class="th-btn-primary rounded-xl px-3 py-2 text-xs font-medium"
                                onclick={() => openUpload(task.id)}
                            >
                                {t('dashboard.staff.upload_deliverable')}
                            </button>
                        {/if}
                    </div>
                </div>

                {#if activeTaskId === task.id}
                    <form
                        class="mt-4 space-y-3 border-t pt-4"
                        style="border-color: var(--th-border)"
                        onsubmit={(e) => {
                            e.preventDefault();
                            submitDeliverable(task);
                        }}
                    >
                        <input
                            type="url"
                            bind:value={deliverableForm.deliverable_url}
                            placeholder={t('dashboard.staff.deliverable_url')}
                            class="w-full rounded-xl border px-3 py-2 text-sm"
                            style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                        />
                        <textarea
                            bind:value={deliverableForm.deliverable_notes}
                            rows="2"
                            placeholder={t('dashboard.staff.deliverable_notes')}
                            class="w-full rounded-xl border px-3 py-2 text-sm"
                            style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                        ></textarea>
                        <input
                            type="file"
                            class="w-full text-sm"
                            style="color: var(--th-text-muted)"
                            onchange={(e) => {
                                const file =
                                    (e.currentTarget as HTMLInputElement)
                                        .files?.[0] ?? null;
                                deliverableForm.deliverable_file = file;
                            }}
                        />
                        <button
                            type="submit"
                            class="th-btn-primary rounded-xl px-4 py-2 text-sm font-medium"
                            disabled={deliverableForm.processing}
                        >
                            {t('dashboard.staff.submit')}
                        </button>
                    </form>
                {/if}
            </div>
        {:else}
            <div class="th-card rounded-2xl p-8 text-center">
                <p class="text-sm" style="color: var(--th-text-muted)">
                    {t('dashboard.staff.no_tasks')}
                </p>
            </div>
        {/each}
    </div>
</section>
