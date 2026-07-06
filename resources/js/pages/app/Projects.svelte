<script lang="ts">
    import { Add01Icon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AppPagination from '@/components/app/AppPagination.svelte';
    import DashboardProjectCard from '@/components/app/DashboardProjectCard.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type {
        CreatableWorkspace,
        DashboardProject,
    } from '@/types/app-dashboard';

    type Paginator<T> = {
        data: T[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };

    let {
        projects = {
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: null,
            to: null,
        },
        search = '',
        workspaceId = null,
        creatableWorkspaces = [],
    }: {
        projects?: Paginator<DashboardProject> | DashboardProject[];
        search?: string;
        workspaceId?: number | null;
        creatableWorkspaces?: CreatableWorkspace[];
    } = $props();

    const projectItems = $derived(
        Array.isArray(projects) ? projects : projects.data,
    );
    const pagination = $derived(Array.isArray(projects) ? null : projects);

    let query = $state(search);
    let showNewProject = $state(false);

    const newProjectUrl = $derived(creatableWorkspaces[0]?.create_url ?? null);

    function applySearch() {
        router.get(
            '/projects',
            {
                q: query.trim() || undefined,
                workspace: workspaceId ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    }
</script>

<AppHead title={t('dashboard.nav.projects')} />

<div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
    <header
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-xl font-bold" style="color: var(--th-text)">
                {t('dashboard.nav.projects')}
            </h1>
            <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
                {t('dashboard.active_projects', {
                    count: projectItems.length,
                })}
            </p>
        </div>
        {#if creatableWorkspaces.length > 1}
            <div class="relative">
                <button
                    type="button"
                    class="th-btn-primary flex items-center gap-2 self-start rounded-xl px-4 py-2.5 text-sm font-medium"
                    onclick={() => (showNewProject = !showNewProject)}
                >
                    <HugeiconsIcon icon={Add01Icon} size={18} color="#fff" />
                    {t('dashboard.new_project')}
                </button>
                {#if showNewProject}
                    <div
                        class="absolute end-0 top-full z-20 mt-2 min-w-48 rounded-xl border p-2 shadow-lg"
                        style="background: var(--th-surface); border-color: var(--th-border)"
                    >
                        {#each creatableWorkspaces as ws (ws.id)}
                            <Link
                                href={ws.create_url}
                                class="block rounded-lg px-3 py-2 text-sm th-hover"
                                style="color: var(--th-text)"
                            >
                                {ws.name}
                            </Link>
                        {/each}
                    </div>
                {/if}
            </div>
        {:else if newProjectUrl}
            <Link
                href={newProjectUrl}
                class="th-btn-primary flex items-center gap-2 self-start rounded-xl px-4 py-2.5 text-sm font-medium"
            >
                <HugeiconsIcon icon={Add01Icon} size={18} color="#fff" />
                {t('dashboard.new_project')}
            </Link>
        {/if}
    </header>

    <form
        class="mb-6 flex max-w-md items-center gap-2 rounded-xl border px-4 py-2.5"
        style="background: var(--th-surface); border-color: var(--th-border)"
        onsubmit={(e) => {
            e.preventDefault();
            applySearch();
        }}
    >
        <HugeiconsIcon
            icon={Search01Icon}
            size={18}
            style="color: var(--th-text-muted)"
        />
        <input
            type="search"
            bind:value={query}
            placeholder={t('dashboard.search_projects')}
            class="min-w-0 flex-1 bg-transparent text-sm outline-none"
            style="color: var(--th-text)"
        />
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        {#each projectItems as project (project.id)}
            <DashboardProjectCard {project} />
        {/each}
    </div>

    {#if pagination}
        <AppPagination paginator={pagination} />
    {/if}
</div>
