<script lang="ts">
    /**
     * A single FAQ entry rendered as an expandable card (matches the Figma
     * support design). The answer slides open and the chevron rotates when
     * `open` is true. Expansion state is owned by the parent so only one item
     * can be open at a time.
     */
    import { ArrowDown01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { slide } from 'svelte/transition';

    type Props = {
        question: string;
        answer: string;
        open: boolean;
        onToggle: () => void;
    };

    let { question, answer, open, onToggle }: Props = $props();
</script>

<div
    class="overflow-hidden rounded-[20px] bg-white shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
>
    <button
        type="button"
        onclick={onToggle}
        aria-expanded={open}
        class="flex w-full items-center justify-between gap-4 px-6 py-5 text-start"
    >
        <p class="text-sm text-black">{question}</p>
        <HugeiconsIcon
            strokeWidth={2}
            icon={ArrowDown01Icon}
            class="size-5 shrink-0 transition-transform duration-200 {open
                ? 'rotate-180 text-brand'
                : 'text-gray-400'}"
        />
    </button>
    {#if open}
        <div
            transition:slide={{ duration: 250 }}
            class="px-6 pb-5 text-sm leading-relaxed text-[#5f5f5f]"
        >
            {answer}
        </div>
    {/if}
</div>
