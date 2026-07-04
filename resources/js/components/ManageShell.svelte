<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';

    export type ManageTab = {
        id: string;
        label: string;
        href: string;
    };

    export type ManageSwitcherItem = {
        id: number;
        name: string;
        href: string;
    };

    type Direction = 'rtl' | 'ltr';

    let {
        title,
        subtitle,
        breadcrumb,
        tabs,
        active,
        switcherLabel,
        switcherItems = [],
        headerActions,
        children,
    }: {
        title: string;
        subtitle?: string;
        breadcrumb?: { label: string; href: string };
        tabs: ManageTab[];
        active: string;
        switcherLabel?: string;
        switcherItems?: ManageSwitcherItem[];
        headerActions?: Snippet;
        children?: Snippet;
    } = $props();

    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );
</script>

<div class="rounded-[24px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)]">
    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="space-y-1 text-start">
            {#if breadcrumb}
                <Link
                    href={breadcrumb.href}
                    class="text-sm text-[#7e7e7e] transition-colors hover:text-brand"
                >
                    {breadcrumb.label}
                </Link>
            {:else if subtitle}
                <p class="text-sm text-[#7e7e7e]">{subtitle}</p>
            {/if}
            <h1 class="text-2xl font-semibold text-black">{title}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {#if switcherItems.length > 1 && switcherLabel}
                <DropdownMenu>
                    <DropdownMenuTrigger
                        class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        {switcherLabel}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        dir={direction}
                        class="min-w-56"
                    >
                        {#each switcherItems as item (item.id)}
                            <DropdownMenuItem>
                                {#snippet child({ props })}
                                    <Link
                                        href={item.href}
                                        class="w-full text-start"
                                        {...props}
                                    >
                                        {item.name}
                                    </Link>
                                {/snippet}
                            </DropdownMenuItem>
                        {/each}
                    </DropdownMenuContent>
                </DropdownMenu>
            {/if}

            {#if headerActions}
                {@render headerActions()}
            {/if}
        </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        {#each tabs as tab (tab.id)}
            <Link
                href={tab.href}
                class={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                    active === tab.id
                        ? 'bg-brand text-white'
                        : 'bg-black/5 text-[#5f5f5f] hover:bg-black/10'
                }`}
            >
                {tab.label}
            </Link>
        {/each}
    </div>

    {#if children}
        {@render children()}
    {/if}
</div>
