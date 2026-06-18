<script lang="ts">
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import type { Snippet } from 'svelte';

    type Props = {
        icon: IconSvgElement;
        label: string;
        value: string | Snippet;
        sub?: string | Snippet;
        size?: 'sm' | 'lg';
        tone?: 'stat' | 'list';
        class?: string;
        children?: Snippet;
    };

    let {
        icon,
        label,
        value,
        sub,
        size,
        tone = 'stat',
        class: className = '',
        children,
    }: Props = $props();

    const variant = $derived(size ?? (sub ? 'lg' : 'sm'));
    const valueClass = $derived(
        variant === 'sm'
            ? 'mt-1 text-[12px] text-[#5f5f5f]'
            : tone === 'list'
              ? 'mt-1 text-[12px] text-[#5f5f5f]'
              : 'mt-1 text-[16px] text-brand',
    );
    const subClass = $derived(
        tone === 'list'
            ? 'mt-1 text-[12px] text-[#5f5f5f]'
            : 'mt-1 text-[12px] text-brand/70',
    );
</script>

{#if variant === 'sm'}
    <article
        class="flex h-[60px] items-center justify-start gap-[10px] rounded-[10px] bg-white px-5 py-2.5 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] {className}"
    >
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-[20px] bg-brand/50 text-white shadow-[0_4px_12px_0_rgba(0,0,0,0.04)]"
        >
            <HugeiconsIcon strokeWidth={2} {icon} class="size-4" />
        </div>
        <div class="flex flex-col items-start text-start leading-none">
            <p class="text-[12px] text-black">{label}</p>
            {#if typeof value === 'string'}
                <p class={valueClass}>{value}</p>
            {:else}
                {@render value()}
            {/if}
        </div>
        {#if children}{@render children()}{/if}
    </article>
{:else}
    <article
        class="flex h-[80px] items-center justify-start gap-[10px] rounded-[10px] bg-white px-5 py-2.5 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] {className}"
    >
        <div
            class="flex size-[50px] shrink-0 items-center justify-center rounded-full bg-brand/50 text-white shadow-[0_4px_12px_0_rgba(0,0,0,0.04)]"
        >
            <HugeiconsIcon strokeWidth={2} {icon} class="size-4" />
        </div>
        <div class="flex flex-1 flex-col items-start text-start leading-none">
            <p class="text-[12px] text-black">{label}</p>
            {#if typeof value === 'string'}
                <p class={valueClass}>{value}</p>
            {:else}
                {@render value()}
            {/if}
            {#if sub}
                {#if typeof sub === 'string'}
                    <p class={subClass}>{sub}</p>
                {:else}
                    {@render sub()}
                {/if}
            {/if}
        </div>
        {#if children}{@render children()}{/if}
    </article>
{/if}
