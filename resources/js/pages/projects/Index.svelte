<script lang="ts">
    import { PlusSignIcon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Form, Link } from '@inertiajs/svelte';
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import ProjectCard from '@/components/ProjectCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import {
        index as projectsIndex,
        show as projectShow,
        create as projectCreate,
    } from '@/routes/projects';
    import type { ProjectListItem, SelectOption, Workspace } from '@/types';

    let {
        workspace,
        projects = [],
        canManage = false,
        filters = { search: '', sort: 'members' },
        filterOptions = { sorts: [] },
    }: {
        workspace: Workspace;
        projects?: ProjectListItem[];
        canManage?: boolean;
        filters?: { search: string; sort: string };
        filterOptions?: { sorts: SelectOption[] };
    } = $props();

    const PAGE_SIZE = 8;
    let visibleCount = $state(PAGE_SIZE);
    const visibleProjects = $derived(projects.slice(0, visibleCount));
    const hasMore = $derived(visibleCount < projects.length);

    $effect(() => {
        void projects;
        visibleCount = PAGE_SIZE;
    });
</script>

<AppHead title={t('project.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={workspace.name}
            title={workspace.name}
            subtitle={t('project.hero_subtitle')}
            foregroundLogo={workspace.logo_url ?? undefined}
            backgroundLogo={workspace.logo_url ?? undefined}
        />

        <Form
            action={projectsIndex(workspace.id).url}
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
                    placeholder={t('project.search_placeholder')}
                    aria-label={t('project.search_aria')}
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
                    {t('project.list_title')}
                </h1>
                {#if canManage}
                    <Link
                        href={projectCreate(workspace.id).url}
                        class="flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={PlusSignIcon}
                            class="size-4"
                        />
                        {t('project.create')}
                    </Link>
                {/if}
            </div>

            {#if projects.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('project.empty')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each visibleProjects as project (project.id)}
                        <div in:fade={{ duration: 250 }}>
                            <ProjectCard
                                name={project.name}
                                description={project.description}
                                members={t('app.members_count', {
                                    count: formatNumber(project.members_count),
                                })}
                                href={projectShow([workspace.id, project.id])
                                    .url}
                                imageUrl={project.image_url}
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
