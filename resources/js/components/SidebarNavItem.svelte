<script lang="ts">
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import type { NavItem } from '@/types';

    let {
        item,
        onNavigate,
        onLogout,
    }: {
        item: NavItem;
        onNavigate?: () => void;
        onLogout?: () => void;
    } = $props();

    const url = currentUrlState();
    const active = $derived(
        !item.isLogout && url.isCurrentUrl(item.href, url.currentUrl),
    );
</script>

{#if item.isLogout}
    <button
        type="button"
        onclick={onLogout}
        data-test="logout-button"
        class="group flex w-full cursor-pointer items-center gap-4 rounded-full px-3 py-2 leading-none transition-colors hover:bg-[#f13e3e]/5"
    >
        <span
            class="flex-1 text-start text-sm text-[#f13e3e] transition-opacity group-hover:opacity-80"
        >
            {item.title}
        </span>
        {#if item.icon}
            <HugeiconsIcon
                strokeWidth={2}
                icon={item.icon}
                class="size-4 shrink-0 text-[#f13e3e] transition-opacity group-hover:opacity-80"
            />
        {/if}
    </button>
{:else if item.isExternal}
    <a
        href={toUrl(item.href)}
        target="_blank"
        rel="noopener noreferrer"
        onclick={onNavigate}
        class="group flex cursor-pointer items-center gap-4 rounded-full px-3 py-2 leading-none transition-colors hover:bg-brand/5"
    >
        <span
            class="flex-1 text-start text-sm text-black transition-colors group-hover:text-brand"
        >
            {item.title}
        </span>
        {#if item.icon}
            <HugeiconsIcon
                strokeWidth={2}
                icon={item.icon}
                class="size-4 shrink-0 text-black transition-colors group-hover:text-brand"
            />
        {/if}
    </a>
{:else}
    <Link
        href={toUrl(item.href)}
        onclick={onNavigate}
        class="group flex cursor-pointer items-center gap-4 rounded-full px-3 py-2 leading-none transition-colors hover:bg-brand/5"
    >
        <span
            class="flex-1 text-start text-sm transition-colors group-hover:text-brand {active
                ? 'font-bold text-brand'
                : 'text-black'}"
        >
            {item.title}
        </span>
        {#if item.icon}
            <HugeiconsIcon
                strokeWidth={2}
                icon={item.icon}
                class="size-4 shrink-0 transition-colors group-hover:text-brand {active
                    ? 'text-brand'
                    : 'text-black'}"
            />
        {/if}
    </Link>
{/if}
