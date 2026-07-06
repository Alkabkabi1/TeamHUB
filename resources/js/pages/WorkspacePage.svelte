<script lang="ts">
    import {
        TaskDaily01Icon,
        UserGroup03Icon,
        UserStar01Icon,
    } from '@hugeicons/core-free-icons';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import ProjectCard from '@/components/ProjectCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type { Workspace } from '@/types';

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
        project_name: string | null;
        url: string | null;
    };

    type ProjectItem = {
        id: number;
        name: string;
        description: string;
        image_url: string | null;
        members_count: number;
        tasks_count: number;
    };

    let {
        workspace,
        projects = [],
        stats = {
            members_count: 0,
            projects_count: 0,
            open_tasks_count: 0,
        },
        recentUpdates = [],
        canManage = false,
        isMember = false,
    }: {
        workspace: Workspace;
        stats?: Stats;
        recentUpdates?: UpdateItem[];
        projects?: ProjectItem[];
        canManage?: boolean;
        isMember?: boolean;
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
            label: t('workspace.stats.category'),
            value: workspace.category ?? '—',
            note: workspace.college ?? t('workspace.stats.student_workspace'),
        },
    ]);
</script>

<AppHead title={workspace.name} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={workspace.name}
            title={t('workspace.organization')}
            subtitle={workspace.name}
            foregroundLogo={workspace.logo_url ?? undefined}
            backgroundLogo={workspace.logo_url ?? undefined}
        />

        {#if canManage}
            <div class="flex justify-end">
                <Link
                    href={`/workspaces/${workspace.id}/manage`}
                    class="cursor-pointer rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('workspace.manage')}
                </Link>
            </div>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('workspace.overview')} />
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

        {#if projects.length > 0}
            <section class="flex flex-col gap-5">
                <SectionHeader
                    title={t('project.title')}
                    href={`/workspaces/${workspace.id}/projects`}
                />
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each projects as project (project.id)}
                        <ProjectCard
                            name={project.name}
                            description={project.description}
                            members={t('app.members_count', {
                                count: formatNumber(project.members_count),
                            })}
                            href={`/workspaces/${workspace.id}/projects/${project.id}`}
                            imageUrl={project.image_url}
                        />
                    {/each}
                </div>
            </section>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('app.updates')} />
            {#if recentUpdates.length === 0}
                <EmptyState message={t('workspace.no_news')} />
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
                                {#if update.project_name}
                                    <p class="text-xs text-[#7e7e7e]">
                                        {update.project_name}
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
                <SectionHeader title={t('workspace.join_workspace')} />
                <div
                    class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-8 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <p class="text-sm text-[#5f5f5f]">
                        {t('workspace.join_workspace_desc')}
                    </p>
                    <Link
                        href={`/workspaces/${workspace.id}/join`}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('workspace.join')}
                    </Link>
                </div>
            </section>
        {/if}
    </div>
</div>
