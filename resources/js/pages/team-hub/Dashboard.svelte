<script lang="ts">
    export const layout = () => null;

    import ActivityFeed from '@/components/team-hub/ActivityFeed.svelte';
    import CalendarWidget from '@/components/team-hub/CalendarWidget.svelte';
    import KpiCard from '@/components/team-hub/KpiCard.svelte';
    import LateTasksWidget from '@/components/team-hub/LateTasksWidget.svelte';
    import ProjectCard from '@/components/team-hub/ProjectCard.svelte';
    import TasksTable from '@/components/team-hub/TasksTable.svelte';
    import { activities, kpis, lateTasks, projects, tasks } from '@/components/team-hub/mock-data';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';
    import { Add01Icon, Notification01Icon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
</script>

{#snippet rightWidgets()}
    <CalendarWidget />
    <LateTasksWidget tasks={lateTasks} />
    <ActivityFeed {activities} />
{/snippet}

<TeamHubLayout
    title="لوحة التحكم — Team Hub"
    activePath="/preview/team-hub/dashboard"
    showRightPanel
    rightPanel={rightWidgets}
>
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
        <header class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-xl font-bold" style="color: var(--th-text)">مرحباً نورة! 👋</h1>
                <p class="text-sm" style="color: var(--th-text-muted)">الاثنين، 9 يونيو 2026</p>
            </div>

            <div class="flex flex-1 items-center gap-3 lg:max-w-xl">
                <div
                    class="flex flex-1 items-center gap-2 rounded-xl border px-4 py-2.5"
                    style="background: var(--th-surface); border-color: var(--th-border)"
                >
                    <HugeiconsIcon icon={Search01Icon} size={18} style="color: var(--th-text-muted)" />
                    <input
                        type="search"
                        placeholder="ابحث عن مشروع، مهمة، أو شخص..."
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                        style="color: var(--th-text)"
                    />
                    <kbd
                        class="hidden rounded-md border px-1.5 py-0.5 text-[10px] sm:inline"
                        style="border-color: var(--th-border); color: var(--th-text-muted)"
                    >⌘K</kbd>
                </div>
                <button
                    type="button"
                    class="relative flex size-10 shrink-0 items-center justify-center rounded-xl border"
                    style="border-color: var(--th-border); background: var(--th-surface)"
                    aria-label="الإشعارات"
                >
                    <HugeiconsIcon icon={Notification01Icon} size={20} style="color: var(--th-text-muted)" />
                    <span
                        class="absolute -top-1 -start-1 flex size-4 items-center justify-center rounded-full text-[9px] font-bold text-white"
                        style="background: var(--th-danger)"
                    >3</span>
                </button>
                <button type="button" class="th-btn-primary flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium">
                    <HugeiconsIcon icon={Add01Icon} size={18} color="#fff" />
                    <span class="hidden sm:inline">مشروع جديد</span>
                </button>
            </div>
        </header>

        <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {#each kpis as kpi (kpi.id)}
                <KpiCard {kpi} />
            {/each}
        </section>

        <section class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold" style="color: var(--th-text)">مشاريعك</h2>
                <a href="/preview/team-hub/projects" class="text-sm font-medium" style="color: var(--th-primary)">
                    عرض الكل
                </a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {#each projects as project (project.id)}
                    <ProjectCard {project} />
                {/each}
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold" style="color: var(--th-text)">مهام اليوم</h2>
                <a href="/preview/team-hub/tasks" class="text-sm font-medium" style="color: var(--th-primary)">
                    عرض الكل
                </a>
            </div>
            <TasksTable tasks={tasks.slice(0, 5)} />
        </section>
    </div>
</TeamHubLayout>
