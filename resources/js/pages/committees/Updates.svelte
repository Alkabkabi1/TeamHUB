<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';
    import type { ClubRef } from '@/types';

    type CommitteeRef = {
        id: number;
        name: string;
        logo_url: string | null;
        status: string;
    };

    type UpdateItem = {
        id: number;
        title: string;
        excerpt: string;
        published_at: string | null;
    };

    let {
        club,
        committee,
        updates = [],
        canManageUpdates = false,
    }: {
        club: ClubRef & { logo_url?: string | null };
        committee: CommitteeRef;
        updates?: UpdateItem[];
        canManageUpdates?: boolean;
    } = $props();

    function removeUpdate(id: number): void {
        router.delete(`/updates/${id}`, { preserveScroll: true });
    }
</script>

<AppHead title={`${committee.name} — ${t('app.updates')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <ProjectManageShell active="updates" {club} {committee} />

        <DashboardCard
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <div class="space-y-1 text-start">
                <h2 class="text-lg font-medium text-black">
                    {t('app.updates')}
                </h2>
                <p class="text-sm text-[#7e7e7e]">{t('news.hero_subtitle')}</p>
            </div>

            {#if canManageUpdates}
                <Link
                    href={`/workspaces/${club.id}/projects/${committee.id}/updates/create`}
                    class="rounded-full bg-brand px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
                >
                    {t('committees.dashboard.add_news')}
                </Link>
            {/if}
        </DashboardCard>

        <DashboardCard class="flex flex-col gap-4">
            {#if updates.length === 0}
                <EmptyState title={t('news.empty')} description="" />
            {:else}
                <div class="space-y-3">
                    {#each updates as update (update.id)}
                        <div class="rounded-[14px] border border-black/10 p-4">
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                            >
                                <div class="space-y-2 text-start">
                                    <p class="text-sm font-medium text-black">
                                        {update.title}
                                    </p>
                                    <p class="text-sm text-[#5f5f5f]">
                                        {update.excerpt}
                                    </p>
                                    {#if update.published_at}
                                        <p class="text-xs text-[#9a9a9a]">
                                            {formatDate(update.published_at)}
                                        </p>
                                    {/if}
                                </div>

                                {#if canManageUpdates}
                                    <div class="flex flex-wrap gap-2">
                                        <Link
                                            href={`/workspaces/${club.id}/projects/${committee.id}/updates/${update.id}/edit`}
                                            class="rounded-full bg-brand/10 px-4 py-2 text-xs font-medium text-brand transition-colors hover:bg-brand/20"
                                        >
                                            {t('committees.dashboard.edit')}
                                        </Link>
                                        <button
                                            type="button"
                                            onclick={() =>
                                                removeUpdate(update.id)}
                                            class="rounded-full bg-rose-50 px-4 py-2 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100"
                                        >
                                            {t('committees.dashboard.delete')}
                                        </button>
                                    </div>
                                {/if}
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </DashboardCard>
    </div>
</div>
