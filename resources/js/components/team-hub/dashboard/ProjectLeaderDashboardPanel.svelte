<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { t } from '@/lib/i18n.svelte';

    type Member = { id: number; name: string };
    type TeamMember = {
        id: number;
        name: string;
        initials: string;
        tasks_total: number;
        tasks_done: number;
        progress: number;
    };
    type ReviewTask = {
        id: number;
        title: string;
        status: string;
        deliverable_url: string | null;
        deliverable_notes: string | null;
        detail_url: string;
    };
    type Project = {
        id: number;
        club_id: number;
        title: string;
        workspace: string;
        progress: number;
        tasks_count: number;
        url: string;
        manage_url: string;
    };

    let {
        project = null,
        team = [],
        reviewQueue = [],
        members = [],
        openTasks = 0,
    }: {
        project?: Project | null;
        team?: TeamMember[];
        reviewQueue?: ReviewTask[];
        members?: Member[];
        openTasks?: number;
    } = $props();

    const taskForm = useForm({
        committee_id: project?.id ?? '',
        title: '',
        assigned_to: members[0]?.id ?? '',
        due_at: '',
        priority: 'medium',
    });

    $effect(() => {
        if (project) {
            taskForm.committee_id = project.id;
        }
    });
</script>

<section class="space-y-6">
    <div>
        <h2 class="text-lg font-bold" style="color: var(--th-text)">
            {t('hub.leader.title')}
        </h2>
        <p class="text-sm" style="color: var(--th-text-muted)">
            {t('hub.leader.subtitle')}
        </p>
    </div>

    {#if project}
        <div
            class="th-card flex flex-col gap-4 rounded-2xl p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-lg font-semibold" style="color: var(--th-text)">
                    {project.title}
                </p>
                <p class="text-sm" style="color: var(--th-text-muted)">
                    {project.workspace} · {project.progress}% · {openTasks}
                    {t('hub.leader.open_tasks')}
                </p>
            </div>
            <div class="flex gap-2">
                <Link
                    href={project.url}
                    class="rounded-xl border px-4 py-2 text-sm"
                    style="border-color: var(--th-border); color: var(--th-text)"
                >
                    {t('hub.nav.tasks')}
                </Link>
                <Link
                    href={project.manage_url}
                    class="th-btn-primary rounded-xl px-4 py-2 text-sm font-medium"
                >
                    {t('hub.view_all')}
                </Link>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="th-card rounded-2xl p-4">
                <h3
                    class="mb-4 text-sm font-semibold"
                    style="color: var(--th-text)"
                >
                    {t('hub.leader.team_progress')}
                </h3>
                <div class="space-y-3">
                    {#each team as member (member.id)}
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9 items-center justify-center rounded-full text-sm font-semibold"
                                style="background: color-mix(in srgb, var(--th-primary) 15%, transparent); color: var(--th-primary)"
                            >
                                {member.initials}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium"
                                    style="color: var(--th-text)"
                                >
                                    {member.name}
                                </p>
                                <div
                                    class="mt-1 h-1.5 overflow-hidden rounded-full"
                                    style="background: var(--th-border)"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        style="width: {member.progress}%; background: var(--th-primary)"
                                    ></div>
                                </div>
                            </div>
                            <span
                                class="text-xs"
                                style="color: var(--th-text-muted)"
                            >
                                {member.tasks_done}/{member.tasks_total}
                            </span>
                        </div>
                    {:else}
                        <p class="text-sm" style="color: var(--th-text-muted)">
                            —
                        </p>
                    {/each}
                </div>
            </div>

            <div class="th-card rounded-2xl p-4">
                <h3
                    class="mb-4 text-sm font-semibold"
                    style="color: var(--th-text)"
                >
                    {t('hub.leader.review_queue')}
                </h3>
                <div class="space-y-3">
                    {#each reviewQueue as task (task.id)}
                        <div
                            class="rounded-xl border p-3"
                            style="border-color: var(--th-border)"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p
                                    class="text-sm font-medium"
                                    style="color: var(--th-text)"
                                >
                                    {task.title}
                                </p>
                                <TaskStatusBadge status="review" />
                            </div>
                            {#if task.deliverable_notes}
                                <p
                                    class="mt-1 text-xs"
                                    style="color: var(--th-text-muted)"
                                >
                                    {task.deliverable_notes}
                                </p>
                            {/if}
                            <Link
                                href={task.detail_url}
                                class="mt-2 inline-block text-xs font-medium"
                                style="color: var(--th-primary)"
                            >
                                {t('hub.leader.view_deliverable')}
                            </Link>
                        </div>
                    {:else}
                        <p class="text-sm" style="color: var(--th-text-muted)">
                            {t('hub.leader.no_reviews')}
                        </p>
                    {/each}
                </div>
            </div>
        </div>

        <form
            class="th-card grid gap-3 rounded-2xl p-4 sm:grid-cols-2 lg:grid-cols-5"
            onsubmit={(e) => {
                e.preventDefault();
                taskForm.post('/hub/leader/tasks', {
                    preserveScroll: true,
                    onSuccess: () => taskForm.reset('title', 'due_at'),
                });
            }}
        >
            <p
                class="sm:col-span-2 lg:col-span-5 text-sm font-semibold"
                style="color: var(--th-text)"
            >
                {t('hub.leader.assign_task')}
            </p>
            <input
                type="text"
                bind:value={taskForm.title}
                placeholder={t('hub.leader.task_title')}
                class="rounded-xl border px-3 py-2 text-sm sm:col-span-2"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                required
            />
            <select
                bind:value={taskForm.assigned_to}
                class="rounded-xl border px-3 py-2 text-sm"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
            >
                <option value="">{t('hub.unassigned')}</option>
                {#each members as member (member.id)}
                    <option value={member.id}>{member.name}</option>
                {/each}
            </select>
            <input
                type="datetime-local"
                bind:value={taskForm.due_at}
                class="rounded-xl border px-3 py-2 text-sm"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
            />
            <button
                type="submit"
                class="th-btn-primary rounded-xl px-4 py-2 text-sm font-medium"
                disabled={taskForm.processing}
            >
                {t('hub.leader.create_task')}
            </button>
        </form>
    {/if}
</section>
