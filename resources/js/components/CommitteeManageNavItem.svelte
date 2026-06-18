<script lang="ts">
    import { TaskDone01Icon } from '@hugeicons/core-free-icons';
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
    import { manage } from '@/routes/committees';
    import type { ManagedCommittee } from '@/types';

    let { onNavigate }: { onNavigate?: () => void } = $props();

    const managedCommittees = $derived(
        (page.props.auth?.user?.managed_committees ?? []) as ManagedCommittee[],
    );
    const direction = $derived((page.props.direction as string) ?? 'rtl');
    const url = currentUrlState();

    const isActive = $derived(
        managedCommittees.some((committee) =>
            url.isCurrentUrl(
                manage({ club: committee.club_id, committee: committee.id }),
                url.currentUrl,
            ),
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

{#if managedCommittees.length === 1}
    <Link
        href={toUrl(
            manage({
                club: managedCommittees[0].club_id,
                committee: managedCommittees[0].id,
            }),
        )}
        onclick={onNavigate}
        class={linkClass}
    >
        <span class={labelClass}>{t('nav.committee_leader_dashboard')}</span>
        <HugeiconsIcon
            strokeWidth={2}
            icon={TaskDone01Icon}
            class={iconClass}
        />
    </Link>
{:else if managedCommittees.length > 1}
    <DropdownMenu>
        <DropdownMenuTrigger class={linkClass}>
            <span class={labelClass}>{t('nav.committee_leader_dashboard')}</span
            >
            <HugeiconsIcon
                strokeWidth={2}
                icon={TaskDone01Icon}
                class={iconClass}
            />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" dir={direction} class="min-w-52">
            {#each managedCommittees as committee (committee.id)}
                <DropdownMenuItem>
                    {#snippet child({ props })}
                        <Link
                            href={toUrl(
                                manage({
                                    club: committee.club_id,
                                    committee: committee.id,
                                }),
                            )}
                            onclick={onNavigate}
                            dir={direction}
                            class="w-full text-start"
                            {...props}
                        >
                            {committee.name}
                        </Link>
                    {/snippet}
                </DropdownMenuItem>
            {/each}
        </DropdownMenuContent>
    </DropdownMenu>
{/if}
