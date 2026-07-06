<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ManageShell from '@/components/ManageShell.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { WorkspaceRef } from '@/types/domain';

    type ManagedWorkspace = { id: number; name: string };

    let {
        workspace,
        active = 'overview',
    }: {
        workspace: WorkspaceRef;
        active?: 'overview' | 'members' | 'settings';
    } = $props();

    const managedWorkspaces = $derived(
        (page.props.auth?.user?.managed_workspaces ?? []) as ManagedWorkspace[],
    );

    const tabs = $derived([
        {
            id: 'overview',
            label: t('app.overview'),
            href: `/workspaces/${workspace.id}/manage`,
        },
        {
            id: 'members',
            label: t('app.manage_members'),
            href: `/workspaces/${workspace.id}/manage/members`,
        },
        {
            id: 'settings',
            label: t('app.manage_settings'),
            href: `/workspaces/${workspace.id}/theme/edit`,
        },
    ]);

    const switcherItems = $derived(
        managedWorkspaces.map((item) => ({
            id: item.id,
            name: item.name,
            href: `/workspaces/${item.id}/manage`,
        })),
    );
</script>

<ManageShell
    title={workspace.name}
    subtitle={t('app.workspace')}
    {tabs}
    {active}
    switcherLabel={t('app.workspace')}
    {switcherItems}
/>
