<script lang="ts">
    import {
        TaskDaily01Icon,
        UserGroup03Icon,
        UserStar01Icon,
    } from '@hugeicons/core-free-icons';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import CommitteeCard from '@/components/CommitteeCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type { Club } from '@/types';

    type Stats = {
        members_count: number;
        projects_count: number;
        open_tasks_count: number;
    };

    type UpdateItem = {
        id: number;
        title: string;
        excerpt: string;
        published_at: string | null;
        committee_name: string | null;
        url: string | null;
    };

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
        stats = {
            members_count: 0,
            projects_count: 0,
            open_tasks_count: 0,
        },
        recentUpdates = [],
        committees = [],
        canManage = false,
        isMember = false,
    }: {
        club: Club;
        stats?: Stats;
        recentUpdates?: UpdateItem[];
        committees?: CommitteeItem[];
        canManage?: boolean;
        isMember?: boolean;
    } = $props();

    const miniStats = $derived([
        {
            icon: UserGroup03Icon,
            label: t('club.stats.members'),
            value: formatNumber(stats.members_count),
            note: t('club.stats.members_note'),
        },
        {
            icon: TaskDaily01Icon,
            label: t('dashboard_student.stats.projects'),
            value: formatNumber(stats.projects_count),
            note: t('app.projects'),
        },
        {
            icon: UserStar01Icon,
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(stats.open_tasks_count),
            note: t('tasks.title'),
        },
        {
            icon: UserStar01Icon,
            label: t('club.stats.category'),
            value: club.category ?? '—',
            note: club.college ?? t('club.stats.student_club'),
        },
    ]);
</script>

<AppHead title={club.name} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={club.name}
            title={t('club.organization')}
            subtitle={club.name}
            foregroundLogo={club.logo_url ?? undefined}
            backgroundLogo={club.logo_url ?? undefined}
        />

        {#if canManage}
            <div class="flex justify-end">
                <Link
                    href={`/workspaces/${club.id}/manage`}
                    class="cursor-pointer rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('club.manage')}
                </Link>
            </div>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('club.overview')} />
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                {#each miniStats as stat (stat.label)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                        sub={stat.note}
                    />
                {/each}
            </div>
        </section>

        {#if committees.length > 0}
            <section class="flex flex-col gap-5">
                <SectionHeader
                    title={t('committees.title')}
                    href={`/workspaces/${club.id}/committees`}
                />
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each committees as committee (committee.id)}
                        <CommitteeCard
                            name={committee.name}
                            description={committee.description}
                            members={t('app.members_count', {
                                count: formatNumber(committee.members_count),
                            })}
                            href={`/workspaces/${club.id}/projects/${committee.id}`}
                            imageUrl={committee.image_url}
                        />
                    {/each}
                </div>
            </section>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('app.updates')} />
            {#if recentUpdates.length === 0}
                <EmptyState message={t('club.no_news')} />
            {:else}
                <div class="grid grid-cols-1 gap-4">
                    {#each recentUpdates as update (update.id)}
                        {#if update.url}
                            <Link
                                href={update.url}
                                class="rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <p class="text-sm font-medium text-black">
                                    {update.title}
                                </p>
                                {#if update.committee_name}
                                    <p class="text-xs text-[#7e7e7e]">
                                        {update.committee_name}
                                    </p>
                                {/if}
                                <p class="mt-1 text-xs text-[#9a9a9a]">
                                    {update.published_at}
                                </p>
                            </Link>
                        {:else}
                            <div
                                class="rounded-[14px] border border-black/10 p-4 text-start"
                            >
                                <p class="text-sm font-medium text-black">
                                    {update.title}
                                </p>
                            </div>
                        {/if}
                    {/each}
                </div>
            {/if}
        </section>

        {#if !isMember}
            <section class="flex flex-col gap-5">
                <SectionHeader title={t('club.join_club')} />
                <div
                    class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-8 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <p class="text-sm text-[#5f5f5f]">
                        {t('club.join_club_desc')}
                    </p>
                    <Link
                        href={`/workspaces/${club.id}/join`}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('clubs.join')}
                    </Link>
                </div>
            </section>
        {/if}
    </div>
</div>
