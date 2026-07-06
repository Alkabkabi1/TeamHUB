<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import WorkspaceManageShell from '@/components/WorkspaceManageShell.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { WorkspaceBranding } from '@/types';

    type RoleOption = { value: string; label: string; isManager: boolean };
    type Member = {
        membershipId: number;
        userId: number;
        name: string;
        email: string;
        major: string;
        joinDate: string;
        volunteerHours: number;
        roles: string[];
        isManager: boolean;
        status: string;
    };
    type PendingMember = {
        id: number;
        name: string;
        details: string;
        time: string;
    };

    let {
        workspace,
        capabilities = [],
        canManageRoles = false,
        roleOptions = [],
        members = [],
        pendingApplications = [],
    }: {
        workspace: WorkspaceBranding & { university?: string | null };
        capabilities?: string[];
        canManageRoles?: boolean;
        roleOptions?: RoleOption[];
        members?: Member[];
        pendingApplications?: PendingMember[];
    } = $props();

    const canManageMembers = $derived(capabilities.includes('manage-members'));

    let term = $state('');
    let results = $state<{ id: number; name: string; email: string }[]>([]);
    let searching = $state(false);
    let editingId = $state<number | null>(null);
    let draftRoles = $state<string[]>([]);

    async function runSearch(): Promise<void> {
        if (term.trim().length < 2) {
            results = [];

            return;
        }

        searching = true;

        try {
            const res = await fetch(
                `/workspaces/${workspace.id}/members/search?q=${encodeURIComponent(term.trim())}`,
                { headers: { Accept: 'application/json' } },
            );
            const data = await res.json();
            results = data.users ?? [];
        } finally {
            searching = false;
        }
    }

    function addMember(userId: number): void {
        router.post(
            `/workspaces/${workspace.id}/members`,
            { user_id: userId, roles: [] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    term = '';
                    results = [];
                },
            },
        );
    }

    function approveRequest(id: number): void {
        router.post(
            `/join-applications/${id}/approve`,
            {},
            { preserveScroll: true },
        );
    }

    function rejectRequest(id: number): void {
        router.post(
            `/join-applications/${id}/reject`,
            {},
            { preserveScroll: true },
        );
    }

    function startEditRoles(member: Member): void {
        editingId = member.membershipId;
        draftRoles = [...member.roles];
    }

    function toggleRole(value: string): void {
        draftRoles = draftRoles.includes(value)
            ? draftRoles.filter((role) => role !== value)
            : [...draftRoles, value];
    }

    function saveRoles(membershipId: number): void {
        router.put(
            `/workspaces/${workspace.id}/members/${membershipId}/roles`,
            { roles: draftRoles },
            { preserveScroll: true, onSuccess: () => (editingId = null) },
        );
    }

    function removeMember(membershipId: number): void {
        router.delete(`/workspaces/${workspace.id}/members/${membershipId}`, {
            preserveScroll: true,
        });
    }
</script>

<AppHead title={`${workspace.name} — ${t('app.manage_members')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <WorkspaceManageShell active="members" {workspace} />

        {#if canManageMembers}
            <DashboardCard class="flex flex-col gap-4">
                <div class="space-y-1 text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('members.add_member')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">
                        {t('members.add_search_placeholder')}
                    </p>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row">
                    <input
                        type="search"
                        bind:value={term}
                        oninput={runSearch}
                        placeholder={t('members.add_search_placeholder')}
                        class="h-11 flex-1 rounded-[10px] border border-black/15 px-4 text-sm outline-none focus:border-brand"
                    />
                </div>

                {#if searching}
                    <p class="text-sm text-[#7e7e7e]">{t('app.loading')}</p>
                {:else if results.length > 0}
                    <div class="space-y-3">
                        {#each results as result (result.id)}
                            <div
                                class="flex items-center justify-between gap-3 rounded-[14px] border border-black/10 px-4 py-3"
                            >
                                <div class="text-start">
                                    <p class="text-sm font-medium text-black">
                                        {result.name}
                                    </p>
                                    <p class="text-xs text-[#7e7e7e]">
                                        {result.email}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onclick={() => addMember(result.id)}
                                    class="rounded-full bg-brand px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
                                >
                                    {t('project.dashboard.add')}
                                </button>
                            </div>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        {/if}

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <DashboardCard class="flex flex-col gap-4">
                <div class="space-y-1 text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('project.dashboard.pending_requests')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">{workspace.name}</p>
                </div>

                {#if pendingApplications.length === 0}
                    <EmptyState
                        title={t('project.dashboard.no_requests')}
                        description=""
                    />
                {:else}
                    <div class="space-y-3">
                        {#each pendingApplications as application (application.id)}
                            <div
                                class="rounded-[14px] border border-black/10 p-4"
                            >
                                <div class="text-start">
                                    <p class="text-sm font-medium text-black">
                                        {application.name}
                                    </p>
                                    <p class="text-xs text-[#7e7e7e]">
                                        {application.details}
                                    </p>
                                    <p class="mt-1 text-xs text-[#9a9a9a]">
                                        {application.time}
                                    </p>
                                </div>

                                {#if canManageMembers}
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            onclick={() =>
                                                approveRequest(application.id)}
                                            class="rounded-full bg-brand px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-brand-dark"
                                        >
                                            {t('project.dashboard.approve')}
                                        </button>
                                        <button
                                            type="button"
                                            onclick={() =>
                                                rejectRequest(application.id)}
                                            class="rounded-full bg-rose-50 px-4 py-2 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100"
                                        >
                                            {t('project.dashboard.reject')}
                                        </button>
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <div class="space-y-1 text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('project.dashboard.current_members')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">
                        {t('app.members_count', { count: members.length })}
                    </p>
                </div>

                {#if members.length === 0}
                    <EmptyState
                        title={t('project.dashboard.no_members')}
                        description=""
                    />
                {:else}
                    <div class="space-y-3">
                        {#each members as member (member.membershipId)}
                            <div
                                class="rounded-[14px] border border-black/10 p-4"
                            >
                                <div
                                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="space-y-1 text-start">
                                        <p
                                            class="text-sm font-medium text-black"
                                        >
                                            {member.name}
                                        </p>
                                        <p class="text-xs text-[#7e7e7e]">
                                            {member.email}
                                        </p>
                                        <p class="text-xs text-[#7e7e7e]">
                                            {member.major}
                                        </p>
                                        <p class="text-xs text-[#9a9a9a]">
                                            {member.joinDate}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        {#each member.roles as role (role)}
                                            <span
                                                class="rounded-full bg-black/5 px-3 py-1 text-xs text-[#5f5f5f]"
                                            >
                                                {role}
                                            </span>
                                        {/each}
                                    </div>
                                </div>

                                {#if canManageRoles || canManageMembers}
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        {#if canManageRoles}
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    startEditRoles(member)}
                                                class="rounded-full bg-brand/10 px-4 py-2 text-xs font-medium text-brand transition-colors hover:bg-brand/20"
                                            >
                                                {t(
                                                    'project.dashboard.edit_roles',
                                                )}
                                            </button>
                                        {/if}

                                        {#if canManageMembers}
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    removeMember(
                                                        member.membershipId,
                                                    )}
                                                class="rounded-full bg-rose-50 px-4 py-2 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100"
                                            >
                                                {t('project.dashboard.remove')}
                                            </button>
                                        {/if}
                                    </div>
                                {/if}

                                {#if editingId === member.membershipId}
                                    <div
                                        class="mt-4 space-y-3 rounded-[14px] bg-black/5 p-4"
                                    >
                                        <div class="flex flex-wrap gap-2">
                                            {#each roleOptions as option (option.value)}
                                                <label
                                                    class="flex items-center gap-2 rounded-full bg-white px-3 py-2 text-xs text-[#5f5f5f]"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={draftRoles.includes(
                                                            option.value,
                                                        )}
                                                        onchange={() =>
                                                            toggleRole(
                                                                option.value,
                                                            )}
                                                    />
                                                    <span>{option.label}</span>
                                                </label>
                                            {/each}
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    saveRoles(
                                                        member.membershipId,
                                                    )}
                                                class="rounded-full bg-brand px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-brand-dark"
                                            >
                                                {t('project.dashboard.save')}
                                            </button>
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    (editingId = null)}
                                                class="rounded-full bg-black/10 px-4 py-2 text-xs font-medium text-[#5f5f5f] transition-colors hover:bg-black/15"
                                            >
                                                {t('app.cancel')}
                                            </button>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        </div>
    </div>
</div>
