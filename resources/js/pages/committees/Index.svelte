<script lang="ts">
    import { PlusSignIcon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Form, Link } from '@inertiajs/svelte';
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import CommitteeCard from '@/components/CommitteeCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import {
        index as committeesIndex,
        show as committeeShow,
        create as committeeCreate,
    } from '@/routes/committees';
    import type { Club, SelectOption } from '@/types';

    type CommitteeItem = {
        id: number;
        name: string;
        description: string;
        image_url: string | null;
        members_count: number;
        tasks_count: number;
    };

    let {
        club,
        committees = [],
        canManage = false,
        filters = { search: '', sort: 'members' },
        filterOptions = { sorts: [] },
    }: {
        club: Club;
        committees?: CommitteeItem[];
        canManage?: boolean;
        filters?: { search: string; sort: string };
        filterOptions?: { sorts: SelectOption[] };
    } = $props();

    const PAGE_SIZE = 8;
    let visibleCount = $state(PAGE_SIZE);
    const visibleCommittees = $derived(committees.slice(0, visibleCount));
    const hasMore = $derived(visibleCount < committees.length);

    $effect(() => {
        void committees;
        visibleCount = PAGE_SIZE;
    });
</script>

<AppHead title={t('committees.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={club.name}
            title={club.name}
            subtitle={t('committees.hero_subtitle')}
            foregroundLogo={club.logo_url ?? undefined}
            backgroundLogo={club.logo_url ?? undefined}
        />

        <Form
            action={committeesIndex(club.id).url}
            method="get"
            options={{
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }}
            class="flex flex-col gap-4 rounded-[20px] bg-white p-4 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] lg:flex-row lg:items-center"
        >
            <div
                class="flex min-h-11 flex-1 items-center gap-2 rounded-full border border-black/10 px-4"
                role="search"
            >
                <input
                    type="search"
                    name="search"
                    value={filters.search}
                    placeholder={t('committees.search_placeholder')}
                    aria-label={t('committees.search_aria')}
                    class="order-2 min-w-0 flex-1 bg-transparent text-start text-sm text-[#5f5f5f] outline-none placeholder:text-[#7e7e7e]"
                />
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Search01Icon}
                    class="order-1 size-4 shrink-0 text-[#7e7e7e]"
                />
            </div>

            <FilterSelect
                class="lg:w-44"
                name="sort"
                ariaLabel={t('app.sort')}
                value={filters.sort}
                options={filterOptions.sorts}
            />

            <button
                type="submit"
                class="min-h-11 rounded-full bg-brand px-8 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
            >
                {t('app.search')}
            </button>
        </Form>

        <section class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-start text-lg text-[#5f5f5f] sm:text-xl">
                    {t('committees.list_title')}
                </h1>
                {#if canManage}
                    <Link
                        href={committeeCreate(club.id).url}
                        class="flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={PlusSignIcon}
                            class="size-4"
                        />
                        {t('committees.create')}
                    </Link>
                {/if}
            </div>

            {#if committees.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('committees.empty')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each visibleCommittees as committee (committee.id)}
                        <div in:fade={{ duration: 250 }}>
                            <CommitteeCard
                                name={committee.name}
                                description={committee.description}
                                members={t('app.members_count', {
                                    count: formatNumber(
                                        committee.members_count,
                                    ),
                                })}
                                href={committeeShow([club.id, committee.id])
                                    .url}
                                imageUrl={committee.image_url}
                            />
                        </div>
                    {/each}
                </div>

                {#if hasMore}
                    <div class="mt-2 flex justify-center">
                        <button
                            type="button"
                            onclick={() => (visibleCount += PAGE_SIZE)}
                            class="min-h-11 rounded-full bg-brand/50 px-10 text-base font-medium text-white transition-colors hover:bg-brand/70"
                        >
                            {t('app.show_more')}
                        </button>
                    </div>
                {/if}
            {/if}
        </section>
    </div>
</div>
