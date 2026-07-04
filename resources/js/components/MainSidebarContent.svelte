<script lang="ts">
    import {
        AiSchedulingIcon,
        BookDownloadIcon,
        DashboardBrowsingIcon,
        Home03Icon,
        News01Icon,
        Notification01Icon,
        Login03Icon,
        Logout03Icon,
        Setting07Icon,
        UserCircleIcon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { page, router } from '@inertiajs/svelte';
    import ClubManageNavItem from '@/components/ClubManageNavItem.svelte';
    import CommitteeManageNavItem from '@/components/CommitteeManageNavItem.svelte';
    import SidebarNavItem from '@/components/SidebarNavItem.svelte';
    import SparkleIcon from '@/components/SparkleIcon.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import {
        home,
        clubs,
        events,
        resources,
        login,
        logout,
        studentDashboard,
        support,
    } from '@/routes';
    import type { NavItem } from '@/types';

    let { onNavigate }: { onNavigate?: () => void } = $props();
    type Direction = 'rtl' | 'ltr' | 'auto';

    const auth = $derived(page.props.auth);
    const role = $derived(auth?.user?.role as string);
    const isAuthenticated = $derived(!!auth?.user);
    // Club supervision is a per-club relationship, not a global role.
    const isClubSupervisor = $derived(!!auth?.user?.is_club_supervisor);
    const isCommitteeLeader = $derived(!!auth?.user?.is_committee_leader);
    const unreadNotificationsCount = $derived(
        Number(auth?.user?.unread_notifications_count ?? 0),
    );
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );

    // Primary navigation. TeamHUB members get a personal dashboard plus a
    // dedicated cross-project tasks view.
    const mainNavItems: NavItem[] = $derived([
        { title: t('nav.home'), href: home(), icon: Home03Icon },
        { title: t('nav.clubs'), href: clubs(), icon: UserGroup03Icon },
        { title: t('nav.events'), href: events(), icon: AiSchedulingIcon },
        { title: t('nav.news'), href: '/news', icon: News01Icon },
        {
            title: t('nav.resources'),
            href: resources(),
            icon: BookDownloadIcon,
        },
        {
            title: t('nav.my_work'),
            href: studentDashboard(),
            icon: UserCircleIcon,
            roles: ['student'],
        },
        {
            title: t('nav.my_tasks'),
            href: '/my-tasks',
            icon: DashboardBrowsingIcon,
            roles: ['student'],
        },
        {
            title: t('nav.notifications'),
            href: '/notifications',
            icon: Notification01Icon,
            badge: unreadNotificationsCount,
            roles: ['student', 'university_staff'],
        },
        ...(isClubSupervisor
            ? [
                  {
                      title: t('nav.club_supervisor_dashboard'),
                      href: '',
                      icon: DashboardBrowsingIcon,
                      kind: 'club-manage' as const,
                  },
              ]
            : []),
        ...(isCommitteeLeader
            ? [
                  {
                      title: t('nav.committee_leader_dashboard'),
                      href: '',
                      icon: DashboardBrowsingIcon,
                      kind: 'committee-manage' as const,
                  },
              ]
            : []),
        {
            title: t('nav.admin_dashboard'),
            href: '/admin',
            icon: DashboardBrowsingIcon,
            roles: ['university_staff'],
            isExternal: true,
        },
    ]);

    const demoQuickLogin = $derived(
        Boolean(
            (page.props.demo as { quick_login?: boolean } | undefined)
                ?.quick_login,
        ),
    );

    // Pinned to the bottom: support, then login/logout.
    const footerNavItems: NavItem[] = $derived([
        { title: t('nav.support'), href: support(), icon: Setting07Icon },
        isAuthenticated
            ? {
                  title: t('nav.logout'),
                  href: logout().url,
                  icon: Logout03Icon,
                  isLogout: true,
              }
            : demoQuickLogin
              ? {
                    title: t('hub.entry_title'),
                    href: home(),
                    icon: Login03Icon,
                }
              : { title: t('nav.login'), href: login(), icon: Login03Icon },
    ]);

    function filterByRole(items: NavItem[]): NavItem[] {
        return items.filter((item) => {
            if (!item.roles) {
                return true;
            }

            if (!isAuthenticated) {
                return false;
            }

            return item.roles.includes(role);
        });
    }

    const filteredMainNavItems = $derived(filterByRole(mainNavItems));
    const filteredFooterNavItems = $derived(filterByRole(footerNavItems));

    function handleLogout() {
        onNavigate?.();
        router.post(logout().url, {}, { onFinish: () => router.flushAll() });
    }
</script>

<div dir={direction} class="flex h-full min-h-0 flex-col px-8 pt-12 pb-8">
    <div class="flex shrink-0 flex-col items-center gap-4 self-center">
        <div
            class="flex size-[84px] items-center justify-center rounded-full bg-brand text-white shadow-[8px_8px_48px_0_rgba(0,0,0,0.08)]"
        >
            <SparkleIcon class="size-[52px]" fillOpacity={0.35} />
        </div>
        {#if isAuthenticated}
            <p class="text-center text-sm text-black dark:text-white">
                {auth?.user?.name}
            </p>
        {/if}
    </div>

    <nav class="thin-scrollbar mt-12 min-h-0 flex-1 overflow-y-auto">
        <ul class="flex flex-col gap-2">
            {#each filteredMainNavItems as item (item.kind ?? toUrl(item.href))}
                <li>
                    {#if item.kind === 'club-manage'}
                        <ClubManageNavItem {onNavigate} />
                    {:else if item.kind === 'committee-manage'}
                        <CommitteeManageNavItem {onNavigate} />
                    {:else}
                        <SidebarNavItem {item} {onNavigate} />
                    {/if}
                </li>
            {/each}
        </ul>
    </nav>

    <ul class="mt-6 flex shrink-0 flex-col gap-2">
        {#each filteredFooterNavItems as item (toUrl(item.href))}
            <li>
                <SidebarNavItem {item} {onNavigate} onLogout={handleLogout} />
            </li>
        {/each}
    </ul>
</div>
