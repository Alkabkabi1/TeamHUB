<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ManageShell from '@/components/ManageShell.svelte';
    import { t } from '@/lib/i18n.svelte';

    type ManagedClub = { id: number; name: string };
    type ClubRef = { id: number; name: string };

    let {
        club,
        active = 'overview',
    }: {
        club: ClubRef;
        active?: 'overview' | 'members' | 'settings';
    } = $props();

    const managedClubs = $derived(
        (page.props.auth?.user?.managed_clubs ?? []) as ManagedClub[],
    );

    const tabs = $derived([
        {
            id: 'overview',
            label: t('app.overview'),
            href: `/clubs/${club.id}/manage`,
        },
        {
            id: 'members',
            label: t('app.manage_members'),
            href: `/clubs/${club.id}/manage/members`,
        },
        {
            id: 'settings',
            label: t('app.manage_settings'),
            href: `/clubs/${club.id}/theme/edit`,
        },
    ]);

    const switcherItems = $derived(
        managedClubs.map((item) => ({
            id: item.id,
            name: item.name,
            href: `/clubs/${item.id}/manage`,
        })),
    );
</script>

<ManageShell
    title={club.name}
    subtitle={t('app.workspace')}
    {tabs}
    {active}
    switcherLabel={t('app.workspace')}
    {switcherItems}
/>
