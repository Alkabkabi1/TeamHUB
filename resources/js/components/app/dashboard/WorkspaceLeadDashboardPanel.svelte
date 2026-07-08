<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Project = {
        id: number;
        title: string;
        workspace: string;
        progress: number;
        tasks_count: number;
        leader: { name: string } | null;
        url: string;
    };

    type Workspace = {
        id: number;
        name: string;
    };

    let {
        workspace = null,
        manageUrl = null,
        projects = [],
        pendingRequests = 0,
        stats = { projects: 0, members: 0, pending_requests: 0 },
    }: {
        workspace?: Workspace | null;
        manageUrl?: string | null;
        projects?: Project[];
        pendingRequests?: number;
        stats?: { projects: number; members: number; pending_requests: number };
    } = $props();
</script>

<section class="space-y-6">
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h2 class="text-lg font-bold" style="color: var(--th-text)">
                {workspace?.name ?? t('dashboard.workspace_lead.title')}
            </h2>
            <p class="text-sm" style="color: var(--th-text-muted)">
                {t('dashboard.workspace_lead.subtitle')}
            </p>
        </div>
        {#if manageUrl}
            <Link
                href={manageUrl}
                class="th-btn-primary inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium"
            >
                {t('dashboard.workspace_lead.manage_workspace')}
            </Link>
        {/if}
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        {#each [{ value: stats.projects, label: t('dashboard.workspace_lead.stats_projects') }, { value: stats.members, label: t('dashboard.workspace_lead.stats_members') }, { value: stats.pending_requests, label: t('dashboard.workspace_lead.stats_requests') }] as stat (stat.label)}
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

    {#if pendingRequests > 0 && manageUrl}
        <div
            class="th-card rounded-2xl border-s-4 p-4"
            style="border-inline-start-color: var(--th-primary)"
        >
            <p class="text-sm font-medium" style="color: var(--th-text)">
                {t('dashboard.workspace_lead.pending_requests')}: {pendingRequests}
            </p>
            <Link
                href={manageUrl}
                class="mt-2 inline-block text-sm font-medium"
                style="color: var(--th-primary)"
            >
                {t('dashboard.view')}
            </Link>
        </div>
    {/if}

    <div>
        <h3 class="mb-4 text-sm font-semibold" style="color: var(--th-text)">
            {t('dashboard.workspace_lead.projects')}
        </h3>
        <div class="space-y-3">
            {#each projects as project (project.id)}
                <div class="th-card rounded-2xl p-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p
                                class="font-medium"
                                style="color: var(--th-text)"
                            >
                                {project.title}
                            </p>
                            <p
                                class="text-xs"
                                style="color: var(--th-text-muted)"
                            >
                                {project.workspace} · {project.leader?.name ??
                                    t('dashboard.admin.no_leader')}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs"
                                style="color: var(--th-text-muted)"
                            >
                                {project.progress}% · {project.tasks_count}
                                {t('dashboard.tasks_count', {
                                    count: project.tasks_count,
                                })}
                            </span>
                            <Link
                                href={project.url}
                                class="text-sm font-medium"
                                style="color: var(--th-primary)"
                            >
                                {t('dashboard.view')}
                            </Link>
                        </div>
                    </div>
                </div>
            {:else}
                <div class="th-card rounded-2xl p-8 text-center">
                    <p class="text-sm" style="color: var(--th-text-muted)">
                        {t('dashboard.workspace_lead.no_projects')}
                    </p>
                </div>
            {/each}
        </div>
    </div>
</section>
