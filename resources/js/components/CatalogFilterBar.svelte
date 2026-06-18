<script lang="ts">
    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Form } from '@inertiajs/svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { SelectOption } from '@/types';

    let {
        action,
        filters,
        filterOptions,
        searchPlaceholder,
        searchAria,
    }: {
        action: string;
        filters: { search: string; tag: string; sort: string };
        filterOptions: { tags: SelectOption[]; sorts: SelectOption[] };
        searchPlaceholder: string;
        searchAria: string;
    } = $props();
</script>

<Form
    {action}
    method="get"
    options={{
        preserveState: true,
        preserveScroll: true,
        replace: true,
    }}
    class="flex flex-col gap-4 rounded-[20px] bg-white p-4 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] lg:flex-row lg:items-center"
>
    <div
        class="flex min-h-11 flex-1 items-center gap-2 rounded-full border border-black/10 px-4"
        role="search"
    >
        <input
            type="search"
            name="search"
            value={filters.search}
            placeholder={searchPlaceholder}
            aria-label={searchAria}
            class="order-2 min-w-0 flex-1 bg-transparent text-start text-sm text-[#5f5f5f] outline-none placeholder:text-[#7e7e7e]"
        />
        <HugeiconsIcon
            strokeWidth={2}
            icon={Search01Icon}
            class="order-1 size-4 shrink-0 text-[#7e7e7e]"
        />
    </div>

    <FilterSelect
        class="lg:w-44"
        name="tag"
        ariaLabel={t('app.tag')}
        allLabel={t('app.all_tags')}
        value={filters.tag}
        options={filterOptions.tags}
    />

    <FilterSelect
        class="lg:w-44"
        name="sort"
        ariaLabel={t('app.sort')}
        value={filters.sort}
        options={filterOptions.sorts}
    />

    <button
        type="submit"
        class="min-h-11 rounded-full bg-brand px-8 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
    >
        {t('app.search')}
    </button>
</Form>
