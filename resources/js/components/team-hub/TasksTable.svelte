<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import PriorityDot from '@/components/team-hub/PriorityDot.svelte';
    import TaskStatusBadge from '@/components/team-hub/TaskStatusBadge.svelte';
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
                    <th class="px-5 py-3 font-medium">المهمة</th>
                    <th class="px-3 py-3 font-medium">المشروع</th>
                    <th class="px-3 py-3 font-medium">الأولوية</th>
                    <th class="px-3 py-3 font-medium">الموعد</th>
                    <th class="px-3 py-3 font-medium">الحالة</th>
                    {#if showAssignee}
                        <th class="px-5 py-3 font-medium">المسؤول</th>
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
                                    class="flex items-center gap-2.5 font-medium hover:underline"
                                    style="color: var(--th-text)"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border-gray-300 accent-[var(--th-primary)]"
                                        checked={task.status === 'done'}
                                        tabindex={-1}
                                        onclick={(e) => e.preventDefault()}
                                    />
                                    {task.title}
                                </Link>
                            {:else}
                                <div class="flex items-center gap-2.5">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border-gray-300 accent-[var(--th-primary)]"
                                        checked={task.status === 'done'}
                                    />
                                    <span
                                        class="font-medium"
                                        style="color: var(--th-text)"
                                        >{task.title}</span
                                    >
                                </div>
                            {/if}
                        </td>
                        <td
                            class="px-3 py-3.5"
                            style="color: var(--th-text-muted)"
                            >{task.project}</td
                        >
                        <td class="px-3 py-3.5"
                            ><PriorityDot priority={task.priority} /></td
                        >
                        <td
                            class="px-3 py-3.5"
                            style="color: var(--th-text-muted)"
                            >{task.dueLabel}</td
                        >
                        <td class="px-3 py-3.5"
                            ><TaskStatusBadge status={task.status} /></td
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
