<script lang="ts">
    import { Add01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Leader = { id: number; name: string; email: string };
    type Workspace = { id: number; name: string };
    type Project = {
        id: number;
        club_id: number;
        title: string;
        workspace: string;
        progress: number;
        tasks_count: number;
        leader: Leader | null;
        url: string;
    };

    let {
        projects = [],
        leaders = [],
        workspaces = [],
        stats = { projects: 0, leaders: 0, open_tasks: 0 },
    }: {
        projects?: Project[];
        leaders?: Leader[];
        workspaces?: Workspace[];
        stats?: { projects: number; leaders: number; open_tasks: number };
    } = $props();

    const assignForm = useForm({
        committee_id: projects[0]?.id ?? '',
        leader_id: leaders[0]?.id ?? '',
    });

    const messageForm = useForm({
        leader_id: leaders[0]?.id ?? '',
        message: '',
    });

    const projectForm = useForm({
        name: '',
        club_id: '' as number | '',
        leader_id: '' as number | '',
    });

    $effect(() => {
        if (workspaces[0] && !projectForm.club_id) {
            projectForm.club_id = workspaces[0].id;
        }

        if (leaders[0] && !projectForm.leader_id) {
            projectForm.leader_id = leaders[0].id;
        }
    });

    let showMessage = $state(false);
    let showNewProject = $state(false);
</script>

<section class="space-y-6">
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h2 class="text-lg font-bold" style="color: var(--th-text)">
                {t('hub.admin.title')}
            </h2>
            <p class="text-sm" style="color: var(--th-text-muted)">
                {t('hub.admin.subtitle')}
            </p>
        </div>
        <button
            type="button"
            class="th-btn-primary inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium"
            onclick={() => (showNewProject = !showNewProject)}
        >
            <HugeiconsIcon icon={Add01Icon} size={18} color="#fff" />
            {t('hub.admin.new_project')}
        </button>
    </div>

    {#if showNewProject}
        <form
            class="th-card grid gap-3 rounded-2xl p-4 sm:grid-cols-2 lg:grid-cols-4"
            onsubmit={(e) => {
                e.preventDefault();
                projectForm.post('/hub/admin/projects', {
                    preserveScroll: true,
                    onSuccess: () => {
                        projectForm.reset('name');
                        showNewProject = false;
                    },
                });
            }}
        >
            <label class="space-y-1 text-sm sm:col-span-2">
                <span style="color: var(--th-text-muted)"
                    >{t('hub.admin.project_name')}</span
                >
                <input
                    type="text"
                    bind:value={projectForm.name}
                    required
                    class="w-full rounded-xl border px-3 py-2"
                    style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                    placeholder={t('hub.admin.project_name')}
                />
            </label>
            {#if workspaces.length > 0}
                <label class="space-y-1 text-sm">
                    <span style="color: var(--th-text-muted)"
                        >{t('hub.admin.workspace')}</span
                    >
                    <select
                        bind:value={projectForm.club_id}
                        class="w-full rounded-xl border px-3 py-2"
                        style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                    >
                        {#each workspaces as workspace (workspace.id)}
                            <option value={workspace.id}
                                >{workspace.name}</option
                            >
                        {/each}
                    </select>
                </label>
            {/if}
            <label class="space-y-1 text-sm">
                <span style="color: var(--th-text-muted)"
                    >{t('hub.admin.leader')}</span
                >
                <select
                    bind:value={projectForm.leader_id}
                    class="w-full rounded-xl border px-3 py-2"
                    style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                >
                    <option value="">{t('hub.admin.no_leader')}</option>
                    {#each leaders as leader (leader.id)}
                        <option value={leader.id}>{leader.name}</option>
                    {/each}
                </select>
            </label>
            <div class="flex items-end sm:col-span-2 lg:col-span-4">
                <button
                    type="submit"
                    class="th-btn-primary rounded-xl px-4 py-2.5 text-sm font-medium"
                    disabled={projectForm.processing}
                >
                    {t('hub.admin.create_project')}
                </button>
            </div>
        </form>
    {/if}

    <div class="grid gap-3 sm:grid-cols-3">
        {#each [{ value: stats.projects, label: t('hub.admin.stats_projects') }, { value: stats.leaders, label: t('hub.admin.stats_leaders') }, { value: stats.open_tasks, label: t('hub.admin.stats_tasks') }] as stat (stat.label)}
            <div class="th-card rounded-2xl p-4">
                <p class="text-2xl font-bold" style="color: var(--th-text)">
                    {stat.value}
                </p>
                <p class="text-xs" style="color: var(--th-text-muted)">
                    {stat.label}
                </p>
            </div>
        {/each}
    </div>

    <form
        class="th-card grid gap-3 rounded-2xl p-4 sm:grid-cols-2 lg:grid-cols-4"
        onsubmit={(e) => {
            e.preventDefault();
            assignForm.post('/hub/admin/assign-leader', {
                preserveScroll: true,
            });
        }}
    >
        <label class="space-y-1 text-sm">
            <span style="color: var(--th-text-muted)"
                >{t('hub.admin.all_projects')}</span
            >
            <select
                bind:value={assignForm.committee_id}
                class="w-full rounded-xl border px-3 py-2"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
            >
                {#each projects as project (project.id)}
                    <option value={project.id}>{project.title}</option>
                {/each}
            </select>
        </label>
        <label class="space-y-1 text-sm">
            <span style="color: var(--th-text-muted)"
                >{t('hub.admin.leader')}</span
            >
            <select
                bind:value={assignForm.leader_id}
                class="w-full rounded-xl border px-3 py-2"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
            >
                {#each leaders as leader (leader.id)}
                    <option value={leader.id}>{leader.name}</option>
                {/each}
            </select>
        </label>
        <div class="flex items-end">
            <button
                type="submit"
                class="th-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-medium"
                disabled={assignForm.processing}
            >
                {t('hub.admin.assign_leader')}
            </button>
        </div>
        <div class="flex items-end">
            <button
                type="button"
                class="w-full rounded-xl border px-4 py-2.5 text-sm"
                style="border-color: var(--th-border); color: var(--th-text)"
                onclick={() => (showMessage = !showMessage)}
            >
                {t('hub.admin.message_leader')}
            </button>
        </div>
    </form>

    {#if showMessage}
        <form
            class="th-card space-y-3 rounded-2xl p-4"
            onsubmit={(e) => {
                e.preventDefault();
                messageForm.post('/hub/admin/message-leader', {
                    preserveScroll: true,
                    onSuccess: () => {
                        messageForm.reset('message');
                        showMessage = false;
                    },
                });
            }}
        >
            <select
                bind:value={messageForm.leader_id}
                class="w-full rounded-xl border px-3 py-2 text-sm"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
            >
                {#each leaders as leader (leader.id)}
                    <option value={leader.id}>{leader.name}</option>
                {/each}
            </select>
            <textarea
                bind:value={messageForm.message}
                rows="3"
                class="w-full rounded-xl border px-3 py-2 text-sm"
                style="border-color: var(--th-border); background: var(--th-surface); color: var(--th-text)"
                placeholder={t('hub.admin.message_placeholder')}
            ></textarea>
            <button
                type="submit"
                class="th-btn-primary rounded-xl px-4 py-2 text-sm font-medium"
                disabled={messageForm.processing}
            >
                {t('hub.admin.send_message')}
            </button>
        </form>
    {/if}

    <div class="th-card overflow-hidden rounded-2xl">
        <div
            class="border-b px-4 py-3 text-sm font-semibold"
            style="border-color: var(--th-border); color: var(--th-text)"
        >
            {t('hub.admin.all_projects')}
        </div>
        <div class="divide-y" style="border-color: var(--th-border)">
            {#each projects as project (project.id)}
                <div
                    class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-medium" style="color: var(--th-text)">
                            {project.title}
                        </p>
                        <p class="text-xs" style="color: var(--th-text-muted)">
                            {project.workspace} · {project.progress}% · {project.tasks_count}
                            مهام
                        </p>
                    </div>
                    <div class="text-sm" style="color: var(--th-text-muted)">
                        {t('hub.admin.leader')}:
                        <span style="color: var(--th-text)">
                            {project.leader?.name ?? t('hub.admin.no_leader')}
                        </span>
                    </div>
                    <Link
                        href={project.url}
                        class="text-sm font-medium"
                        style="color: var(--th-primary)"
                    >
                        {t('hub.view_all')}
                    </Link>
                </div>
            {/each}
        </div>
    </div>
</section>
