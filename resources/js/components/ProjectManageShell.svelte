<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { t } from '@/lib/i18n.svelte';

    type ClubRef = { id: number; name: string };
    type CommitteeRef = {
        id: number;
        name: string;
        logo_url?: string | null;
        status?: string;
    };
    type ManagedCommittee = { id: number; name: string; club_id: number };
    type Direction = 'rtl' | 'ltr';

    let {
        club,
        committee,
        active = 'overview',
    }: {
        club: ClubRef;
        committee: CommitteeRef;
        active?: 'overview' | 'tasks' | 'files' | 'updates' | 'settings';
    } = $props();

    const managedCommittees = $derived(
        (page.props.auth?.user?.managed_committees ?? []) as ManagedCommittee[],
    );
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );

    const tabs = $derived([
        {
            id: 'overview',
            label: t('app.overview'),
            href: `/clubs/${club.id}/committees/${committee.id}/manage`,
        },
        {
            id: 'tasks',
            label: t('tasks.title'),
            href: `/clubs/${club.id}/committees/${committee.id}/tasks`,
        },
        {
            id: 'files',
            label: t('app.files'),
            href: `/clubs/${club.id}/committees/${committee.id}/files`,
        },
        {
            id: 'updates',
            label: t('app.updates'),
            href: `/clubs/${club.id}/committees/${committee.id}/updates`,
        },
    ]);
</script>

<div class="rounded-[24px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)]">
    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="space-y-1 text-start">
            <Link
                href={`/clubs/${club.id}/manage`}
                class="text-sm text-[#7e7e7e] transition-colors hover:text-brand"
            >
                {club.name}
            </Link>
            <h1 class="text-2xl font-semibold text-black">{committee.name}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {#if managedCommittees.length > 1}
                <DropdownMenu>
                    <DropdownMenuTrigger
                        class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        {t('app.project')}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        dir={direction}
                        class="min-w-56"
                    >
                        {#each managedCommittees as item (item.id)}
                            <DropdownMenuItem>
                                {#snippet child({ props })}
                                    <Link
                                        href={`/clubs/${item.club_id}/committees/${item.id}/manage`}
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

            <Link
                href={`/clubs/${club.id}/committees/${committee.id}/edit`}
                class={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                    active === 'settings'
                        ? 'bg-brand text-white'
                        : 'bg-black/5 text-[#5f5f5f] hover:bg-black/10'
                }`}
            >
                {t('app.manage_settings')}
            </Link>
        </div>
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
