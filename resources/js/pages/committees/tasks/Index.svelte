<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';

    type ClubRef = { id: number; name: string };
    type CommitteeRef = { id: number; name: string };
    type MemberOption = { value: number; label: string };
    type SelectOption = { value: string; label: string };
    type TaskSummary = {
        id: number;
        title: string;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high' | 'urgent';
        due_at: string | null;
        assignee_name: string | null;
        creator_name: string | null;
        has_deliverable: boolean;
    };

    let {
        club,
        committee,
        tasks = [],
        members = [],
        statusOptions = [],
        priorityOptions = [],
        canManageTasks = false,
        manageUrl,
    }: {
        club: ClubRef;
        committee: CommitteeRef;
        tasks?: TaskSummary[];
        members?: MemberOption[];
        statusOptions?: SelectOption[];
        priorityOptions?: SelectOption[];
        canManageTasks?: boolean;
        manageUrl: string;
    } = $props();

    let filter = $state<'all' | TaskSummary['status']>('all');
    let search = $state('');
    let pageLoading = $state(false);

    const skeletonItems = [0, 1, 2];

    const form = useForm({
        title: '',
        description: '',
        assigned_to: '',
        priority: 'medium',
        status: 'todo',
        due_at: '',
    });

    const filteredTasks = $derived(
        tasks.filter((task) => {
            const matchesStatus = filter === 'all' || task.status === filter;
            const query = search.trim().toLowerCase();
            const matchesSearch =
                query === '' ||
                task.title.toLowerCase().includes(query) ||
                (task.assignee_name ?? '').toLowerCase().includes(query) ||
                (task.creator_name ?? '').toLowerCase().includes(query);

            return matchesStatus && matchesSearch;
        }),
    );

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post(`/clubs/${club.id}/committees/${committee.id}/tasks`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.priority = 'medium';
                form.status = 'todo';
            },
        });
    }

    function detailUrl(taskId: number): string {
        return `/clubs/${club.id}/committees/${committee.id}/tasks/${taskId}`;
    }

    $effect(() => {
        const offStart = router.on('start', () => {
            pageLoading = true;
        });
        const offFinish = router.on('finish', () => {
            pageLoading = false;
        });

        return () => {
            offStart();
            offFinish();
        };
    });
</script>

<AppHead title={`${committee.name} — ${t('tasks.title')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd] dark:bg-[#0f172a]">
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <ProjectManageShell active="tasks" {club} {committee} />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
                <p class="text-sm text-[#7e7e7e] dark:text-[#94a3b8]">
                    {club.name}
                </p>
                <h1 class="text-2xl font-semibold text-black dark:text-white">
                    {committee.name} — {t('tasks.title')}
                </h1>
                <p class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]">
                    {t('tasks.list_description')}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href={manageUrl}
                    class="rounded-full bg-brand/15 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/25"
                >
                    {t('tasks.back_to_project')}
                </Link>
            </div>
        </div>

        {#if canManageTasks}
            <form
                onsubmit={submit}
                class="grid gap-4 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)] lg:grid-cols-2"
            >
                <div class="flex flex-col gap-2 lg:col-span-2">
                    <h2 class="text-lg font-medium text-black dark:text-white">
                        {t('tasks.create_title')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e] dark:text-[#94a3b8]">
                        {t('tasks.subtitle')}
                    </p>
                </div>

                <div class="flex flex-col gap-2 lg:col-span-2">
                    <label
                        for="title"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.title')}</label
                    >
                    <input
                        id="title"
                        name="title"
                        bind:value={form.title}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    />
                    <InputError message={form.errors.title} />
                </div>

                <div class="flex flex-col gap-2 lg:col-span-2">
                    <label
                        for="description"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.description')}</label
                    >
                    <textarea
                        id="description"
                        name="description"
                        bind:value={form.description}
                        rows="4"
                        class="rounded-[10px] border border-black/15 bg-white px-4 py-3 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    ></textarea>
                    <InputError message={form.errors.description} />
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        for="assigned_to"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.assignee')}</label
                    >
                    <select
                        id="assigned_to"
                        name="assigned_to"
                        bind:value={form.assigned_to}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    >
                        <option value="">{t('tasks.unassigned')}</option>
                        {#each members as member (member.value)}
                            <option value={member.value}>{member.label}</option>
                        {/each}
                    </select>
                    <InputError message={form.errors.assigned_to} />
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        for="priority"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.priority')}</label
                    >
                    <select
                        id="priority"
                        name="priority"
                        bind:value={form.priority}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    >
                        {#each priorityOptions as option (option.value)}
                            <option value={option.value}>{option.label}</option>
                        {/each}
                    </select>
                    <InputError message={form.errors.priority} />
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        for="status"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.status')}</label
                    >
                    <select
                        id="status"
                        name="status"
                        bind:value={form.status}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    >
                        {#each statusOptions.filter( (option) => ['todo', 'in_progress'].includes(option.value), ) as option (option.value)}
                            <option value={option.value}>{option.label}</option>
                        {/each}
                    </select>
                    <InputError message={form.errors.status} />
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        for="due_at"
                        class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        >{t('tasks.due_date')}</label
                    >
                    <input
                        id="due_at"
                        name="due_at"
                        type="datetime-local"
                        bind:value={form.due_at}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    />
                    <InputError message={form.errors.due_at} />
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        disabled={form.processing}
                        class="rounded-full bg-brand px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
                    >
                        {t('tasks.create_title')}
                    </button>
                </div>
            </form>
        {/if}

        <div
            class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
        >
            <div
                class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <input
                    type="search"
                    bind:value={search}
                    placeholder={t('tasks.search_placeholder')}
                    class="h-11 w-full rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white lg:max-w-sm"
                />

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class={`rounded-full px-3 py-1.5 text-xs font-medium ${filter === 'all' ? 'bg-brand text-white' : 'bg-brand/10 text-brand'}`}
                        onclick={() => (filter = 'all')}
                    >
                        {t('tasks.all_statuses')}
                    </button>

                    {#each statusOptions as option (option.value)}
                        <button
                            type="button"
                            class={`rounded-full px-3 py-1.5 text-xs font-medium ${filter === option.value ? 'bg-brand text-white' : 'bg-brand/10 text-brand'}`}
                            onclick={() =>
                                (filter =
                                    option.value as TaskSummary['status'])}
                        >
                            {option.label}
                        </button>
                    {/each}
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-[#7e7e7e] dark:text-[#94a3b8]"
                    >{t('tasks.status_reference')}</span
                >
                <TaskStatusBadge status="todo" />
                <TaskStatusBadge status="in_progress" />
                <TaskStatusBadge status="review" />
                <TaskStatusBadge status="done" />
            </div>

            {#if pageLoading}
                <div class="space-y-3">
                    {#each skeletonItems as item (item)}
                        <div
                            class="animate-pulse rounded-[16px] border border-black/10 p-4 dark:border-white/10"
                        >
                            <div
                                class="h-4 w-1/3 rounded bg-black/10 dark:bg-white/10"
                            ></div>
                            <div
                                class="mt-3 h-3 w-2/3 rounded bg-black/10 dark:bg-white/10"
                            ></div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <div
                                    class="h-10 rounded bg-black/10 dark:bg-white/10"
                                ></div>
                                <div
                                    class="h-10 rounded bg-black/10 dark:bg-white/10"
                                ></div>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else if filteredTasks.length === 0}
                <div
                    class="rounded-2xl border border-dashed border-black/10 px-6 py-12 text-center text-sm text-[#7e7e7e] dark:border-white/10 dark:text-[#94a3b8]"
                >
                    {t('tasks.empty')}
                </div>
            {:else}
                <div class="space-y-3 lg:hidden">
                    {#each filteredTasks as task (task.id)}
                        <div
                            class="rounded-[16px] border border-black/10 p-4 dark:border-white/10"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-2">
                                    <Link
                                        href={detailUrl(task.id)}
                                        class="block text-base font-medium text-black transition-colors hover:text-brand dark:text-white"
                                    >
                                        {task.title}
                                    </Link>
                                    <div class="flex flex-wrap gap-2">
                                        <TaskStatusBadge status={task.status} />
                                        <TaskPriorityBadge
                                            priority={task.priority}
                                        />
                                    </div>
                                </div>
                                {#if task.has_deliverable}
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                    >
                                        {t('tasks.deliverable_flag')}
                                    </span>
                                {/if}
                            </div>

                            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt
                                        class="text-[#7e7e7e] dark:text-[#94a3b8]"
                                    >
                                        {t('tasks.assignee')}
                                    </dt>
                                    <dd
                                        class="text-[#5f5f5f] dark:text-[#cbd5e1]"
                                    >
                                        {task.assignee_name ??
                                            t('tasks.unassigned')}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-[#7e7e7e] dark:text-[#94a3b8]"
                                    >
                                        {t('tasks.created_by')}
                                    </dt>
                                    <dd
                                        class="text-[#5f5f5f] dark:text-[#cbd5e1]"
                                    >
                                        {task.creator_name ??
                                            t('tasks.not_set')}
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt
                                        class="text-[#7e7e7e] dark:text-[#94a3b8]"
                                    >
                                        {t('tasks.due_date')}
                                    </dt>
                                    <dd
                                        class="text-[#5f5f5f] dark:text-[#cbd5e1]"
                                    >
                                        {task.due_at
                                            ? formatDate(task.due_at, {
                                                  year: 'numeric',
                                                  month: 'short',
                                                  day: 'numeric',
                                              })
                                            : t('tasks.not_set')}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    {/each}
                </div>

                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead>
                            <tr
                                class="border-b border-black/10 text-start text-[#7e7e7e] dark:border-white/10 dark:text-[#94a3b8]"
                            >
                                <th class="px-4 py-3 font-medium"
                                    >{t('tasks.title')}</th
                                >
                                <th class="px-4 py-3 font-medium"
                                    >{t('tasks.priority')}</th
                                >
                                <th class="px-4 py-3 font-medium"
                                    >{t('tasks.status')}</th
                                >
                                <th class="px-4 py-3 font-medium"
                                    >{t('tasks.assignee')}</th
                                >
                                <th class="px-4 py-3 font-medium"
                                    >{t('tasks.due_date')}</th
                                >
                            </tr>
                        </thead>
                        <tbody>
                            {#each filteredTasks as task (task.id)}
                                <tr class="border-b border-black/5 align-top">
                                    <td class="px-4 py-4">
                                        <Link
                                            href={detailUrl(task.id)}
                                            class="block font-medium text-black transition-colors hover:text-brand dark:text-white"
                                        >
                                            {task.title}
                                        </Link>
                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[#7e7e7e] dark:text-[#94a3b8]"
                                        >
                                            <span
                                                >{t('tasks.created_by')}: {task.creator_name ??
                                                    t('tasks.not_set')}</span
                                            >
                                            {#if task.has_deliverable}
                                                <span
                                                    class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700"
                                                >
                                                    {t(
                                                        'tasks.deliverable_flag',
                                                    )}
                                                </span>
                                            {/if}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4"
                                        ><TaskPriorityBadge
                                            priority={task.priority}
                                        /></td
                                    >
                                    <td class="px-4 py-4"
                                        ><TaskStatusBadge
                                            status={task.status}
                                        /></td
                                    >
                                    <td
                                        class="px-4 py-4 text-[#5f5f5f] dark:text-[#cbd5e1]"
                                        >{task.assignee_name ??
                                            t('tasks.unassigned')}</td
                                    >
                                    <td
                                        class="px-4 py-4 text-[#5f5f5f] dark:text-[#cbd5e1]"
                                    >
                                        {task.due_at
                                            ? formatDate(task.due_at, {
                                                  year: 'numeric',
                                                  month: 'short',
                                                  day: 'numeric',
                                              })
                                            : t('tasks.not_set')}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
</div>
