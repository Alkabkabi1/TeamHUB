<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { t } from '@/lib/i18n.svelte';

    type ManagedClub = { id: number; name: string };
    type ClubRef = { id: number; name: string };
    type Direction = 'rtl' | 'ltr';

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
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
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
</script>

<div class="rounded-[24px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)]">
    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="space-y-1 text-start">
            <p class="text-sm text-[#7e7e7e]">{t('app.workspace')}</p>
            <h1 class="text-2xl font-semibold text-black">{club.name}</h1>
        </div>

        {#if managedClubs.length > 1}
            <DropdownMenu>
                <DropdownMenuTrigger
                    class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                >
                    {t('app.workspace')}
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    dir={direction}
                    class="min-w-56"
                >
                    {#each managedClubs as item (item.id)}
                        <DropdownMenuItem>
                            {#snippet child({ props })}
                                <Link
                                    href={`/clubs/${item.id}/manage`}
                                    class="w-full text-start"
                                    {...props}
                                >
                                    {item.name}
                                </Link>
                            {/snippet}
                        </DropdownMenuItem>
                    {/each}
                </DropdownMenuContent>
            </DropdownMenu>
        {/if}
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        {#each tabs as tab (tab.id)}
            <Link
                href={tab.href}
                class={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                    active === tab.id
                        ? 'bg-brand text-white'
                        : 'bg-black/5 text-[#5f5f5f] hover:bg-black/10'
                }`}
            >
                {tab.label}
            </Link>
        {/each}
    </div>
</div>
