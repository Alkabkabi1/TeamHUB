<script lang="ts">
    import { cn } from '@/lib/utils';

    type Tone = 'brand' | 'red' | 'neutral';

    /**
     * Small pill badge used for member status and event status across the
     * dashboard. Replaces the inlined `rounded-full bg-brand/25 …` spans.
     */
    let {
        label,
        tone = 'brand',
        class: className = '',
    }: {
        label: string;
        tone?: Tone;
        class?: string;
    } = $props();

    const tones: Record<Tone, string> = {
        brand: 'bg-brand/25 text-brand',
        red: 'bg-[#f13e3e]/10 text-[#f13e3e]',
        neutral: 'bg-black/5 text-[#5f5f5f]',
    };
</script>

<span
    class={cn(
        'inline-flex items-center justify-center rounded-full px-2.5 py-1 text-[12px] whitespace-nowrap',
        tones[tone],
        className,
    )}
>
    {label}
</span>
