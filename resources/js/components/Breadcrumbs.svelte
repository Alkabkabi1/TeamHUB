<script lang="ts">
    // @ts-nocheck
    import type { UrlMethodPair } from '@inertiajs/core';
    import { Link } from '@inertiajs/svelte';
    import {
        Breadcrumb,
        BreadcrumbItem,
        BreadcrumbLink,
        BreadcrumbList,
        BreadcrumbPage,
        BreadcrumbSeparator,
    } from '@/components/ui/breadcrumb';
    import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs: BreadcrumbItemType[];
    } = $props();

    function linkHref(
        href: BreadcrumbItemType['href'],
    ): string | UrlMethodPair | undefined {
        return href ?? undefined;
    }
</script>

<Breadcrumb>
    <BreadcrumbList>
        {#each breadcrumbs as item, index (item.href)}
            <BreadcrumbItem>
                {#if index === breadcrumbs.length - 1}
                    <BreadcrumbPage>{item.title}</BreadcrumbPage>
                {:else if item.href}
                    <BreadcrumbLink>
                        {#snippet child({ props })}
                            <Link href={linkHref(item.href)} {...props}>
                                {item.title}
                            </Link>
                        {/snippet}
                    </BreadcrumbLink>
                {:else}
                    <BreadcrumbPage>{item.title}</BreadcrumbPage>
                {/if}
            </BreadcrumbItem>
            {#if index !== breadcrumbs.length - 1}
                <BreadcrumbSeparator />
            {/if}
        {/each}
    </BreadcrumbList>
</Breadcrumb>
