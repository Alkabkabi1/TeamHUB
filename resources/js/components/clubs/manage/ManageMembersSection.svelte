<script lang="ts">
    import {
        Add01Icon,
        Cancel01Icon,
        Delete02Icon,
        Search01Icon,
        User03Icon,
        UserSettings01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { router, useHttp } from '@inertiajs/svelte';
    import {
        search as searchMembers,
        store as storeMember,
        updateRoles as updateMemberRoles,
        destroy as destroyMember,
    } from '@/actions/App/Http/Controllers/WorkspaceMemberController';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import SectionHeading from '@/components/SectionHeading.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type {
        ClubManageFoundUser,
        ClubManageMember,
        ClubManagePendingMember,
        ClubManageRoleOption,
    } from '@/types/club-manage';

    let {
        clubId,
        members = [],
        pendingApplications = [],
        canManageRoles = false,
        roleOptions = [],
    }: {
        clubId: number;
        members?: ClubManageMember[];
        pendingApplications?: ClubManagePendingMember[];
        canManageRoles?: boolean;
        roleOptions?: ClubManageRoleOption[];
    } = $props();

    const managerRoleOptions = $derived(
        roleOptions.filter((option) => option.isManager),
    );

    function managerRolesFor(member: ClubManageMember): string[] {
        const managerValues = managerRoleOptions.map((option) => option.value);

        return member.roles.filter((role) => managerValues.includes(role));
    }

    let memberSearch = $state('');

    const http = useHttp();
    let addOpen = $state(false);
    let userQuery = $state('');
    let userResults = $state<ClubManageFoundUser[]>([]);
    let searching = $state(false);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function onUserQueryInput(): void {
        clearTimeout(searchTimer);
        const q = userQuery.trim();

        if (q.length < 2) {
            userResults = [];

            return;
        }

        searchTimer = setTimeout(async () => {
            searching = true;

            try {
                const result = (await http.submit(
                    searchMembers({ workspace: clubId }, { query: { q } }),
                )) as { users: ClubManageFoundUser[] };
                userResults = result.users ?? [];
            } catch {
                userResults = [];
            } finally {
                searching = false;
            }
        }, 250);
    }

    function addMember(userId: number): void {
        router.post(
            storeMember.url(clubId),
            { user_id: userId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    addOpen = false;
                    userQuery = '';
                    userResults = [];
                },
            },
        );
    }

    function removeMember(member: ClubManageMember): void {
        if (confirm(t('members.confirm_remove'))) {
            router.delete(
                destroyMember.url({
                    workspace: clubId,
                    membership: member.membershipId,
                }),
                { preserveScroll: true },
            );
        }
    }

    let roleOpen = $state(false);
    let roleTarget = $state<ClubManageMember | null>(null);
    let selectedRoles = $state<string[]>([]);

    function openRoleModal(member: ClubManageMember): void {
        roleTarget = member;
        selectedRoles = managerRolesFor(member);
        roleOpen = true;
    }

    function toggleRole(value: string): void {
        selectedRoles = selectedRoles.includes(value)
            ? selectedRoles.filter((role) => role !== value)
            : [...selectedRoles, value];
    }

    function saveRoles(): void {
        if (roleTarget === null) {
            return;
        }

        router.put(
            updateMemberRoles.url({
                workspace: clubId,
                membership: roleTarget.membershipId,
            }),
            { roles: selectedRoles },
            {
                preserveScroll: true,
                onSuccess: () => {
                    roleOpen = false;
                    roleTarget = null;
                },
            },
        );
    }

    const filteredMembers = $derived(
        memberSearch.trim() === ''
            ? members
            : members.filter((member) => {
                  const query = memberSearch.trim().toLowerCase();

                  return (
                      member.name.toLowerCase().includes(query) ||
                      member.email.toLowerCase().includes(query)
                  );
              }),
    );
</script>

<section aria-label={t('members.section_title')} class="flex flex-col gap-5">
    <SectionHeading title={t('members.section_title')}>
        {#snippet action()}
            <button
                type="button"
                onclick={() => (addOpen = true)}
                class="flex cursor-pointer items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Add01Icon}
                    class="size-4"
                />
                {t('members.add_member')}
            </button>
        {/snippet}
    </SectionHeading>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <DashboardCard class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-start text-[15px] text-black">
                    {t('members.current_members', {
                        count: members.length,
                    })}
                </p>
                <label
                    class="flex h-[30px] min-w-[160px] max-w-[220px] flex-1 items-center gap-2 rounded-full border border-black/20 bg-white px-4"
                >
                    <input
                        type="search"
                        bind:value={memberSearch}
                        placeholder={t('members.search_placeholder')}
                        class="order-2 h-full min-w-0 flex-1 bg-transparent text-start text-[12px] text-[#5f5f5f] outline-none placeholder:text-[#7e7e7e]"
                    />
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Search01Icon}
                        class="order-1 size-3 shrink-0 text-[#7e7e7e]"
                    />
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-start">
                    <thead>
                        <tr class="text-[12px] text-[#5f5f5f]">
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_member')}
                            </th>
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_major')}
                            </th>
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_join_date')}
                            </th>
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_hours')}
                            </th>
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_status')}
                            </th>
                            <th class="px-2 pb-3 text-start font-normal">
                                {t('members.col_actions')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each filteredMembers as member (member.membershipId)}
                            <tr class="border-t border-black/5">
                                <td class="px-2 py-3">
                                    <div
                                        class="flex flex-col items-start leading-tight"
                                    >
                                        <p class="text-[12px] text-black">
                                            {member.name}
                                        </p>
                                        <p class="text-[12px] text-[#5f5f5f]">
                                            {member.email}
                                        </p>
                                    </div>
                                </td>
                                <td
                                    class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                >
                                    {member.major}
                                </td>
                                <td
                                    class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                >
                                    {member.joinDate}
                                </td>
                                <td
                                    class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                >
                                    {t('members.hours_unit', {
                                        count: formatNumber(
                                            member.volunteerHours,
                                        ),
                                    })}
                                </td>
                                <td class="px-2 py-3">
                                    <StatusBadge label={member.status} />
                                </td>
                                <td class="px-2 py-3">
                                    <div
                                        class="flex items-center justify-start gap-2"
                                    >
                                        {#if canManageRoles}
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    openRoleModal(member)}
                                                title={t(
                                                    'members.manage_roles',
                                                )}
                                                class="flex cursor-pointer items-center gap-1 rounded-full bg-brand/10 px-3 py-1.5 text-[11px] text-brand transition-colors hover:bg-brand/20"
                                            >
                                                <HugeiconsIcon
                                                    strokeWidth={2}
                                                    icon={UserSettings01Icon}
                                                    class="size-3.5"
                                                />
                                                {t('members.manage_roles')}
                                            </button>
                                        {/if}
                                        <button
                                            type="button"
                                            onclick={() => removeMember(member)}
                                            title={t('members.remove')}
                                            class="flex cursor-pointer items-center gap-1 rounded-full bg-[#f13e3e]/10 px-3 py-1.5 text-[11px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                        >
                                            <HugeiconsIcon
                                                strokeWidth={2}
                                                icon={Delete02Icon}
                                                class="size-3.5"
                                            />
                                            {t('members.remove')}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                        {#if filteredMembers.length === 0}
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-2 py-6 text-center text-[12px] text-[#7e7e7e]"
                                >
                                    {t('members.no_members')}
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </DashboardCard>

        <DashboardCard class="flex flex-col gap-4">
            <p class="text-start text-[15px] text-black">
                {t('members.pending_title', {
                    count: pendingApplications.length,
                })}
            </p>
            <div class="flex flex-col gap-4">
                {#each pendingApplications as request (request.id)}
                    <div
                        class="flex flex-wrap items-center gap-4 rounded-[12px] border border-black/20 bg-white p-4"
                    >
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand/25 text-brand"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={User03Icon}
                                class="size-5"
                            />
                        </div>
                        <div
                            class="flex min-w-0 flex-1 flex-col items-start text-start leading-tight"
                        >
                            <p class="text-[14px] font-medium text-black">
                                {request.name}
                            </p>
                            <p class="mt-1 text-[12px] text-[#5f5f5f]">
                                {request.details}
                            </p>
                            <p class="mt-1 text-[12px] text-brand/60">
                                {request.time}
                            </p>
                        </div>
                        <div
                            class="flex w-full items-center justify-end gap-2 sm:w-auto"
                        >
                            <button
                                type="button"
                                onclick={() =>
                                    router.post(
                                        `/join-applications/${request.id}/approve`,
                                        {},
                                        { preserveScroll: true },
                                    )}
                                class="cursor-pointer rounded-full bg-brand px-4 py-2 text-[12px] text-white transition-colors hover:bg-brand-dark"
                            >
                                {t('members.approve')}
                            </button>
                            <button
                                type="button"
                                onclick={() =>
                                    router.post(
                                        `/join-applications/${request.id}/reject`,
                                        {},
                                        { preserveScroll: true },
                                    )}
                                class="cursor-pointer rounded-full bg-[#f13e3e] px-4 py-2 text-[12px] text-white transition-colors hover:bg-[#cc3434]"
                            >
                                {t('members.reject')}
                            </button>
                        </div>
                    </div>
                {/each}
                {#if pendingApplications.length === 0}
                    <p class="text-start text-[12px] text-[#7e7e7e]">
                        {t('members.no_pending')}
                    </p>
                {/if}
            </div>
        </DashboardCard>
    </div>
</section>

<Dialog bind:open={addOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{t('members.add_member')}</DialogTitle>
            <DialogDescription>
                {t('members.add_search_placeholder')}
            </DialogDescription>
        </DialogHeader>
        <div class="flex flex-col gap-3">
            <label
                class="flex h-10 items-center gap-2 rounded-[10px] border border-black/20 bg-white px-4"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Search01Icon}
                    class="size-4 shrink-0 text-[#7e7e7e]"
                />
                <input
                    type="search"
                    bind:value={userQuery}
                    oninput={onUserQueryInput}
                    placeholder={t('members.add_search_placeholder')}
                    class="h-full min-w-0 flex-1 bg-transparent text-start text-[13px] text-black outline-none placeholder:text-[#7e7e7e]"
                />
            </label>
            <div class="max-h-64 overflow-y-auto">
                {#if searching}
                    <p class="px-1 py-2 text-[12px] text-[#7e7e7e]">…</p>
                {:else if userResults.length === 0 && userQuery.trim().length >= 2}
                    <p class="px-1 py-2 text-[12px] text-[#7e7e7e]">
                        {t('members.no_results')}
                    </p>
                {:else}
                    <ul class="flex flex-col gap-1">
                        {#each userResults as found (found.id)}
                            <li
                                class="flex items-center justify-between gap-3 rounded-[10px] border border-black/10 px-3 py-2"
                            >
                                <div
                                    class="flex min-w-0 flex-col items-start leading-tight"
                                >
                                    <span class="text-[13px] text-black"
                                        >{found.name}</span
                                    >
                                    <span class="text-[11px] text-[#7e7e7e]"
                                        >{found.email}</span
                                    >
                                </div>
                                <button
                                    type="button"
                                    onclick={() => addMember(found.id)}
                                    class="shrink-0 cursor-pointer rounded-full bg-brand px-4 py-1.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                >
                                    {t('members.add')}
                                </button>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </div>
        </div>
    </DialogContent>
</Dialog>

<Dialog bind:open={roleOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{t('members.manage_roles')}</DialogTitle>
            <DialogDescription>{roleTarget?.name}</DialogDescription>
        </DialogHeader>
        <div class="flex flex-col gap-2">
            {#each managerRoleOptions as option (option.value)}
                <label
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-[10px] border border-black/10 px-3 py-2.5"
                >
                    <span class="text-[13px] text-black">{option.label}</span>
                    <input
                        type="checkbox"
                        checked={selectedRoles.includes(option.value)}
                        onchange={() => toggleRole(option.value)}
                        class="size-4 accent-brand"
                    />
                </label>
            {/each}
        </div>
        <div class="mt-2 flex items-center justify-end gap-2">
            <button
                type="button"
                onclick={() => (roleOpen = false)}
                class="flex cursor-pointer items-center gap-1 rounded-full bg-black/5 px-5 py-2 text-[12px] text-[#5f5f5f] transition-colors hover:bg-black/10"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Cancel01Icon}
                    class="size-3.5"
                />
                {t('members.cancel')}
            </button>
            <button
                type="button"
                onclick={saveRoles}
                class="cursor-pointer rounded-full bg-brand px-5 py-2 text-[12px] text-white transition-colors hover:bg-brand-dark"
            >
                {t('members.save_roles')}
            </button>
        </div>
    </DialogContent>
</Dialog>
