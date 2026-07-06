<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ManageShell from '@/components/ManageShell.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ProjectRef, WorkspaceRef } from '@/types/domain';

    type ManagedProject = { id: number; name: string; workspace_id: number };

    let {
        workspace,
        project,
        active = 'overview',
    }: {
        workspace: WorkspaceRef;
        project: ProjectRef;
        active?: 'overview' | 'tasks' | 'files' | 'updates' | 'settings';
    } = $props();

    const managedProjects = $derived(
        (page.props.auth?.user?.managed_projects ?? []) as ManagedProject[],
    );

    const tabs = $derived([
        {
            id: 'overview',
            label: t('app.overview'),
            href: `/workspaces/${workspace.id}/projects/${project.id}/manage`,
        },
        {
            id: 'tasks',
            label: t('tasks.title'),
            href: `/workspaces/${workspace.id}/projects/${project.id}/tasks`,
        },
        {
            id: 'files',
            label: t('app.files'),
            href: `/workspaces/${workspace.id}/projects/${project.id}/files`,
        },
        {
            id: 'updates',
            label: t('app.updates'),
            href: `/workspaces/${workspace.id}/projects/${project.id}/updates`,
        },
    ]);

    const switcherItems = $derived(
        managedProjects.map((item) => ({
            id: item.id,
            name: item.name,
            href: `/workspaces/${item.workspace_id}/projects/${item.id}/manage`,
        })),
    );
</script>

<ManageShell
    title={project.name}
    breadcrumb={{
        label: workspace.name,
        href: `/workspaces/${workspace.id}/manage`,
    }}
    {tabs}
    {active}
    switcherLabel={t('app.project')}
    {switcherItems}
>
    {#snippet headerActions()}
        <Link
            href={`/workspaces/${workspace.id}/projects/${project.id}/edit`}
            class={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                active === 'settings'
                    ? 'bg-brand text-white'
                    : 'bg-black/5 text-[#5f5f5f] hover:bg-black/10'
            }`}
        >
            {t('app.manage_settings')}
        </Link>
    {/snippet}
</ManageShell>
