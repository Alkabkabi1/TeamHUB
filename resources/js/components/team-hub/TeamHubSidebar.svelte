<script lang="ts">
    import {
        Calendar03Icon,
        ChartHistogramIcon,
        DashboardSquare01Icon,
        File01Icon,
        Folder01Icon,
        Home01Icon,
        Notification01Icon,
        Settings01Icon,
        TaskDone01Icon,
        Upload04Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import DemoRoleSwitcher from '@/components/team-hub/DemoRoleSwitcher.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { themeState } from '@/lib/theme.svelte';
    import type { HubNavItem, HubWorkspace } from '@/types/team-hub';

    const iconMap: Record<string, IconSvgElement> = {
        home: Home01Icon,
        projects: Folder01Icon,
        tasks: TaskDone01Icon,
        deliverable: Upload04Icon,
        team: UserGroup03Icon,
        calendar: Calendar03Icon,
        files: File01Icon,
        reports: ChartHistogramIcon,
        notifications: Notification01Icon,
    };

    let {
        activePath,
    }: {
        activePath?: string;
    } = $props();

    const currentPath = $derived(activePath ?? (page.url as string));
    const theme = themeState();
    const auth = $derived(page.props.auth);
    const hub = $derived(
        page.props.hub as
            | { nav: HubNavItem[]; workspaces: HubWorkspace[] }
            | null
            | undefined,
    );
    const demo = $derived(
        page.props.demo as
            | {
                  quick_login: boolean;
                  accounts: {
                      email: string;
                      name: string;
                      role: string;
                      label: string;
                  }[];
              }
            | undefined,
    );
    const navItems = $derived(hub?.nav ?? []);
    const workspaces = $derived(hub?.workspaces ?? []);
    const userName = $derived(auth?.user?.name ?? '');
    const displayName = $derived.by(() => {
        const email = auth?.user?.email;

        if (demo?.quick_login && email) {
            const account = demo.accounts.find((item) => item.email === email);

            if (account) {
                return account.label ?? t(`auth.demo_roles.${account.role}`);
            }
        }

        return userName;
    });
    const displayInitial = $derived(displayName.charAt(0) || '?');

    function toggleTheme() {
        theme.toggleAppearance();
    }
</script>

<aside
    class="flex w-60 shrink-0 flex-col border-e"
    style="background: var(--th-surface); border-color: var(--th-border)"
>
    <div class="border-b p-5" style="border-color: var(--th-border)">
        <div class="flex items-center gap-2.5">
            <div
                class="th-btn-primary flex size-9 items-center justify-center rounded-xl"
            >
                <HugeiconsIcon
                    icon={DashboardSquare01Icon}
                    size={20}
                    color="#fff"
                />
            </div>
            <div>
                <p class="font-bold" style="color: var(--th-text)">Team Hub</p>
                <p class="text-xs" style="color: var(--th-text-muted)">
                    {t('hub.platform_tagline')}
                </p>
            </div>
        </div>
    </div>

    <nav class="thin-scrollbar flex-1 space-y-1 overflow-y-auto p-3">
        {#each navItems as item (item.href + item.label)}
            {@const isActive = currentPath.startsWith(item.href)}
            <Link
                href={item.href}
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                    {isActive ? 'th-nav-active font-medium' : ''}"
                style={!isActive ? 'color: var(--th-text-muted)' : undefined}
            >
                <HugeiconsIcon icon={iconMap[item.icon]} size={20} />
                <span class="flex-1">{item.label}</span>
                {#if item.badge}
                    <span
                        class="flex size-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold text-white"
                        style="background: var(--th-danger)"
                    >
                        {item.badge}
                    </span>
                {/if}
            </Link>
        {/each}

        {#if workspaces.length > 0}
            <div class="pt-4">
                <p
                    class="mb-2 px-3 text-xs font-medium"
                    style="color: var(--th-text-muted)"
                >
                    {t('hub.workspaces')}
                </p>
                {#each workspaces as ws (ws.id)}
                    <Link
                        href={ws.url}
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm transition-colors th-hover"
                        style="color: var(--th-text-muted)"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-md text-xs font-bold text-white"
                            style="background: {ws.color}"
                        >
                            {ws.letter}
                        </span>
                        <span class="truncate">{ws.name}</span>
                    </Link>
                {/each}
            </div>
        {/if}
    </nav>

    <div class="space-y-3 border-t p-4" style="border-color: var(--th-border)">
        {#if demo?.quick_login && demo.accounts.length > 0}
            <DemoRoleSwitcher />
        {/if}

        {#if auth?.user}
            <div class="flex items-center gap-3">
                <span
                    class="flex size-9 items-center justify-center rounded-full text-sm font-semibold"
                    style="background: color-mix(in srgb, var(--th-primary) 15%, transparent); color: var(--th-primary)"
                >
                    {displayInitial}
                </span>
                <div class="min-w-0 flex-1">
                    <p
                        class="truncate text-sm font-medium"
                        style="color: var(--th-text)"
                    >
                        {displayName}
                    </p>
                    {#if !demo?.quick_login}
                        <p
                            class="truncate text-xs"
                            style="color: var(--th-text-muted)"
                        >
                            {auth.user.email}
                        </p>
                    {/if}
                </div>
                <button type="button" class="opacity-50" aria-label="الإعدادات">
                    <HugeiconsIcon
                        icon={Settings01Icon}
                        size={18}
                        style="color: var(--th-text-muted)"
                    />
                </button>
            </div>
        {/if}

        <button
            type="button"
            class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-sm"
            style="border-color: var(--th-border); color: var(--th-text-muted)"
            onclick={toggleTheme}
        >
            <span
                >{theme.resolvedAppearance() === 'dark'
                    ? 'الوضع الداكن'
                    : 'الوضع الفاتح'}</span
            >
            <span
                class="relative h-5 w-9 rounded-full transition-colors"
                style="background: {theme.resolvedAppearance() === 'dark'
                    ? 'var(--th-primary)'
                    : 'var(--th-border)'}"
            >
                <span
                    class="absolute top-0.5 size-4 rounded-full bg-white transition-all"
                    style="inset-inline-start: {theme.resolvedAppearance() ===
                    'dark'
                        ? '18px'
                        : '2px'}"
                ></span>
            </span>
        </button>
    </div>
</aside>
