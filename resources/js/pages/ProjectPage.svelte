<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        TaskDaily01Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import {
        join as projectJoin,
        manage as projectManage,
    } from '@/routes/projects';
    import type { WorkspaceRef } from '@/types';

    type Project = {
        id: number;
        name: string;
        description: string | null;
        theme: string | null;
        logo_url: string | null;
        image_url: string | null;
        status: string;
    };

    type Stats = {
        members_count: number;
        tasks_count: number;
        open_tasks_count: number;
    };

    type UpdateItem = {
        id: number;
        title: string;
        excerpt: string;
        published_at: string | null;
        url: string;
    };

    let {
        workspace,
        project,
        stats = {
            members_count: 0,
            tasks_count: 0,
            open_tasks_count: 0,
        },
        recentUpdates = [],
        canManage = false,
        membershipStatus = null,
        canRequestToJoin = false,
    }: {
        workspace: WorkspaceRef & { logo_url?: string | null };
        project: Project;
        stats?: Stats;
        recentUpdates?: UpdateItem[];
        canManage?: boolean;
        membershipStatus?: string | null;
        canRequestToJoin?: boolean;
    } = $props();

    const miniStats = $derived([
        {
            icon: UserGroup03Icon,
            label: t('workspace.stats.members'),
            value: formatNumber(stats.members_count),
            note: t('workspace.stats.members_note'),
        },
        {
            icon: TaskDaily01Icon,
            label: t('tasks.title'),
            value: formatNumber(stats.tasks_count),
            note: t('app.projects'),
        },
        {
            icon: CheckmarkCircle01Icon,
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(stats.open_tasks_count),
            note: t('tasks.title'),
        },
    ]);

    let joining = $state(false);

    function requestToJoin(): void {
        router.post(
            projectJoin([workspace.id, project.id]).url,
            {},
            {
                preserveScroll: true,
                onStart: () => (joining = true),
                onFinish: () => (joining = false),
            },
        );
    }
</script>

<AppHead title={project.name} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={project.name}
            title={workspace.name}
            subtitle={project.name}
            foregroundLogo={project.logo_url ?? workspace.logo_url ?? undefined}
            backgroundLogo={project.logo_url ?? workspace.logo_url ?? undefined}
        />

        <div class="flex items-center justify-between gap-4">
            <Link
                href={`/workspaces/${workspace.id}/projects`}
                class="text-sm text-[#7e7e7e] transition-colors hover:text-brand"
            >
                {t('project.back_to_list')}
            </Link>
            {#if canManage}
                <Link
                    href={projectManage([workspace.id, project.id]).url}
                    class="cursor-pointer rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('project.manage')}
                </Link>
            {/if}
        </div>

        {#if project.description}
            <p class="text-start text-sm leading-relaxed text-[#5f5f5f]">
                {project.description}
            </p>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('workspace.overview')} />
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
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

        <section class="flex flex-col gap-5">
            <SectionHeader
                title={t('app.updates')}
                href={`/workspaces/${workspace.id}/projects/${project.id}/updates`}
            />
            {#if recentUpdates.length === 0}
                <EmptyState message={t('workspace.no_news')} />
            {:else}
                <div class="grid grid-cols-1 gap-4">
                    {#each recentUpdates as update (update.id)}
                        <Link
                            href={update.url}
                            class="rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                        >
                            <p class="text-sm font-medium text-black">
                                {update.title}
                            </p>
                            <p class="mt-1 text-xs text-[#9a9a9a]">
                                {update.published_at}
                            </p>
                        </Link>
                    {/each}
                </div>
            {/if}
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('project.join')} />
            <div
                class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-8 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                {#if membershipStatus === 'approved'}
                    <p class="text-sm text-brand">
                        {t('project.is_member')}
                    </p>
                {:else if membershipStatus === 'pending'}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('project.request_pending')}
                    </p>
                {:else if membershipStatus === 'rejected'}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('project.request_rejected_state')}
                    </p>
                {:else if canRequestToJoin}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('project.join_desc')}
                    </p>
                    <button
                        type="button"
                        disabled={joining}
                        onclick={requestToJoin}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {t('project.request_to_join')}
                    </button>
                {:else}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('project.join_requires_workspace')}
                    </p>
                    <Link
                        href={`/workspaces/${workspace.id}/join`}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('workspace.join')}
                    </Link>
                {/if}
            </div>
        </section>
    </div>
</div>
