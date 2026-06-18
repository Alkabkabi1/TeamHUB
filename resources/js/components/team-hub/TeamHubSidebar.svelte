<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { page } from '@inertiajs/svelte';
    import { navItems, workspaces } from '@/components/team-hub/mock-data';
    import {
        Calendar03Icon,
        DashboardSquare01Icon,
        File01Icon,
        Folder01Icon,
        Home01Icon,
        Notification01Icon,
        Settings01Icon,
        TaskDone01Icon,
        Upload04Icon,
        UserGroup03Icon,
        ChartHistogramIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';

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
        dark = $bindable(false),
        activePath,
    }: {
        dark?: boolean;
        activePath?: string;
    } = $props();

    const currentPath = $derived(activePath ?? (page.url as string));

    function toggleTheme() {
        dark = !dark;
    }
</script>

<aside
    class="flex w-60 shrink-0 flex-col border-e"
    style="background: var(--th-surface); border-color: var(--th-border)"
>
    <div class="border-b p-5" style="border-color: var(--th-border)">
        <div class="flex items-center gap-2.5">
            <div class="th-btn-primary flex size-9 items-center justify-center rounded-xl">
                <HugeiconsIcon icon={DashboardSquare01Icon} size={20} color="#fff" />
            </div>
            <div>
                <p class="font-bold" style="color: var(--th-text)">Team Hub</p>
                <p class="text-xs" style="color: var(--th-text-muted)">منصة إدارة المشاريع</p>
            </div>
        </div>
    </div>

    <nav class="thin-scrollbar flex-1 space-y-1 overflow-y-auto p-3">
        {#each navItems as item (item.href + item.label)}
            {@const isActive = item.href !== '#' && currentPath.startsWith(item.href)}
            {#if item.href === '#'}
                <span
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm opacity-50"
                    style="color: var(--th-text-muted)"
                >
                    <HugeiconsIcon icon={iconMap[item.icon]} size={20} />
                    <span class="flex-1">{item.label}</span>
                    {#if item.badge}
                        <span
                            class="flex size-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                            style="background: var(--th-danger)"
                        >
                            {item.badge}
                        </span>
                    {/if}
                </span>
            {:else}
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
                            class="flex size-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                            style="background: var(--th-danger)"
                        >
                            {item.badge}
                        </span>
                    {/if}
                </Link>
            {/if}
        {/each}

        <div class="pt-4">
            <p class="mb-2 px-3 text-xs font-medium" style="color: var(--th-text-muted)">مساحات العمل</p>
            {#each workspaces as ws (ws.name)}
                <div
                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm"
                    style="color: var(--th-text-muted)"
                >
                    <span
                        class="flex size-6 items-center justify-center rounded-md text-xs font-bold text-white"
                        style="background: {ws.color}"
                    >
                        {ws.letter}
                    </span>
                    <span class="truncate">{ws.name}</span>
                </div>
            {/each}
        </div>
    </nav>

    <div class="space-y-3 border-t p-4" style="border-color: var(--th-border)">
        <div class="flex items-center gap-3">
            <span
                class="flex size-9 items-center justify-center rounded-full text-sm font-semibold"
                style="background: color-mix(in srgb, var(--th-primary) 15%, transparent); color: var(--th-primary)"
            >
                ن
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium" style="color: var(--th-text)">نورة</p>
                <p class="truncate text-xs" style="color: var(--th-text-muted)">مديرة المنتج</p>
            </div>
            <button type="button" class="opacity-50" aria-label="الإعدادات">
                <HugeiconsIcon icon={Settings01Icon} size={18} style="color: var(--th-text-muted)" />
            </button>
        </div>

        <button
            type="button"
            class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-sm"
            style="border-color: var(--th-border); color: var(--th-text-muted)"
            onclick={toggleTheme}
        >
            <span>{dark ? 'الوضع الداكن' : 'الوضع الفاتح'}</span>
            <span
                class="relative h-5 w-9 rounded-full transition-colors"
                style="background: {dark ? 'var(--th-primary)' : 'var(--th-border)'}"
            >
                <span
                    class="absolute top-0.5 size-4 rounded-full bg-white transition-all"
                    style="inset-inline-start: {dark ? '18px' : '2px'}"
                ></span>
            </span>
        </button>
    </div>
</aside>
