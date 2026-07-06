<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';

    type PaginatorLink = {
        url: string | null;
        label: string;
        active: boolean;
    };

    type Paginator<T> = {
        data: T[];
        links: PaginatorLink[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };

    let {
        paginator,
    }: {
        paginator: Paginator<unknown>;
    } = $props();

    const prevLink = $derived(
        paginator.links.find(
            (link) =>
                link.label.includes('Previous') ||
                link.label.includes('السابق'),
        ),
    );
    const nextLink = $derived(
        paginator.links.find(
            (link) =>
                link.label.includes('Next') || link.label.includes('التالي'),
        ),
    );
</script>

{#if paginator.last_page > 1}
    <div
        class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row"
    >
        <p class="text-sm" style="color: var(--th-text-muted)">
            {t('dashboard.pagination.showing', {
                from: paginator.from ?? 0,
                to: paginator.to ?? 0,
                total: paginator.total,
            })}
        </p>
        <div class="flex items-center gap-2">
            {#if prevLink?.url}
                <Link
                    href={prevLink.url}
                    class="rounded-xl border px-3 py-1.5 text-sm"
                    style="border-color: var(--th-border); color: var(--th-text)"
                    preserve-scroll
                >
                    {t('dashboard.pagination.previous')}
                </Link>
            {/if}
            <span class="text-sm" style="color: var(--th-text-muted)">
                {paginator.current_page} / {paginator.last_page}
            </span>
            {#if nextLink?.url}
                <Link
                    href={nextLink.url}
                    class="rounded-xl border px-3 py-1.5 text-sm"
                    style="border-color: var(--th-border); color: var(--th-text)"
                    preserve-scroll
                >
                    {t('dashboard.pagination.next')}
                </Link>
            {/if}
        </div>
    </div>
{/if}
