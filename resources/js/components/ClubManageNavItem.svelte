<script lang="ts">
    import { DashboardBrowsingIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import { manage } from '@/routes/clubs';
    import type { ManagedClub } from '@/types';

    let { onNavigate }: { onNavigate?: () => void } = $props();

    const managedClubs = $derived(
        (page.props.auth?.user?.managed_clubs ?? []) as ManagedClub[],
    );
    const direction = $derived((page.props.direction as string) ?? 'rtl');
    const url = currentUrlState();

    // Highlight whenever the current page is any managed club's dashboard.
    const isActive = $derived(
        managedClubs.some((club) =>
            url.isCurrentUrl(manage(club.id), url.currentUrl),
        ),
    );

    const linkClass =
        'group flex w-full cursor-pointer items-center gap-4 rounded-full px-3 py-2 leading-none transition-colors hover:bg-brand/5';
    const labelClass = $derived(
        `flex-1 text-start text-sm transition-colors group-hover:text-brand ${
            isActive ? 'font-bold text-brand' : 'text-black'
        }`,
    );
    const iconClass = $derived(
        `size-4 shrink-0 transition-colors group-hover:text-brand ${
            isActive ? 'text-brand' : 'text-black'
        }`,
    );
</script>

{#if managedClubs.length === 1}
    <!-- Manages exactly one club: a direct dashboard link. -->
    <Link
        href={toUrl(manage(managedClubs[0].id))}
        onclick={onNavigate}
        class={linkClass}
    >
        <span class={labelClass}>{t('nav.club_supervisor_dashboard')}</span>
        <HugeiconsIcon
            strokeWidth={2}
            icon={DashboardBrowsingIcon}
            class={iconClass}
        />
    </Link>
{:else if managedClubs.length > 1}
    <!-- Manages several clubs: a dropdown to pick which dashboard to open. -->
    <DropdownMenu>
        <DropdownMenuTrigger class={linkClass}>
            <span class={labelClass}>{t('nav.club_supervisor_dashboard')}</span>
            <HugeiconsIcon
                strokeWidth={2}
                icon={DashboardBrowsingIcon}
                class={iconClass}
            />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" dir={direction} class="min-w-52">
            {#each managedClubs as club (club.id)}
                <DropdownMenuItem>
                    {#snippet child({ props })}
                        <Link
                            href={toUrl(manage(club.id))}
                            onclick={onNavigate}
                            dir={direction}
                            class="w-full text-start"
                            {...props}
                        >
                            {club.name}
                        </Link>
                    {/snippet}
                </DropdownMenuItem>
            {/each}
        </DropdownMenuContent>
    </DropdownMenu>
{/if}
