<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { HubTask } from '@/types/team-hub';

    let {
        tasks,
        showAssignee = true,
    }: {
        tasks: HubTask[];
        showAssignee?: boolean;
    } = $props();
</script>

<div class="th-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead>
                <tr
                    class="border-b text-start"
                    style="border-color: var(--th-border); color: var(--th-text-muted)"
                >
                    <th class="px-5 py-3 font-medium">{t('tasks.title')}</th>
                    <th class="px-3 py-3 font-medium"
                        >{t('hub.nav.projects')}</th
                    >
                    <th class="px-3 py-3 font-medium">{t('tasks.priority')}</th>
                    <th class="px-3 py-3 font-medium">{t('tasks.due_date')}</th>
                    <th class="px-3 py-3 font-medium">{t('tasks.status')}</th>
                    {#if showAssignee}
                        <th class="px-5 py-3 font-medium"
                            >{t('tasks.assignee')}</th
                        >
                    {/if}
                </tr>
            </thead>
            <tbody>
                {#each tasks as task (task.id)}
                    <tr
                        class="border-b transition-colors hover:bg-black/[0.03]"
                        style="border-color: var(--th-border)"
                    >
                        <td class="px-5 py-3.5">
                            {#if task.url}
                                <Link
                                    href={task.url}
                                    class="font-medium hover:underline"
                                    style="color: var(--th-text)"
                                >
                                    {task.title}
                                </Link>
                            {:else}
                                <span
                                    class="font-medium"
                                    style="color: var(--th-text)"
                                    >{task.title}</span
                                >
                            {/if}
                        </td>
                        <td
                            class="px-3 py-3.5"
                            style="color: var(--th-text-muted)"
                            >{task.project}</td
                        >
                        <td class="px-3 py-3.5"
                            ><TaskPriorityBadge
                                priority={task.priority}
                                variant="dot"
                            /></td
                        >
                        <td
                            class="px-3 py-3.5"
                            style="color: var(--th-text-muted)"
                            >{task.dueLabel}</td
                        >
                        <td class="px-3 py-3.5"
                            ><TaskStatusBadge
                                status={task.status}
                                variant="hub"
                            /></td
                        >
                        {#if showAssignee}
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-flex size-7 items-center justify-center rounded-full text-xs font-medium"
                                    style="background: color-mix(in srgb, var(--th-primary) 15%, transparent); color: var(--th-primary)"
                                    title={task.assignee.name}
                                >
                                    {task.assignee.initials}
                                </span>
                            </td>
                        {/if}
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</div>
