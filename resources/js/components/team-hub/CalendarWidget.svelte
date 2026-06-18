<script lang="ts">
    import { ArrowLeft01Icon, ArrowRight01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';

    const days = ['أحد', 'إثن', 'ثلا', 'أرب', 'خمي', 'جمع', 'سبت'];
    const monthDays = Array.from({ length: 30 }, (_, i) => i + 1);
    const today = 9;
</script>

<div class="th-card p-4">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold" style="color: var(--th-text)">يونيو 2026</h3>
        <div class="flex gap-1">
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded-lg th-hover"
                aria-label="الشهر السابق"
            >
                <HugeiconsIcon icon={ArrowRight01Icon} size={16} style="color: var(--th-text-muted)" />
            </button>
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded-lg th-hover"
                aria-label="الشهر التالي"
            >
                <HugeiconsIcon icon={ArrowLeft01Icon} size={16} style="color: var(--th-text-muted)" />
            </button>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-xs" style="color: var(--th-text-muted)">
        {#each days as day (day)}
            <span class="py-1 font-medium">{day}</span>
        {/each}
        {#each monthDays as day (day)}
            <button
                type="button"
                class="flex size-8 items-center justify-center rounded-full text-sm transition-colors
                    {day === today
                    ? 'th-btn-primary font-semibold text-white'
                    : 'th-hover'}"
                style={day !== today ? 'color: var(--th-text)' : undefined}
            >
                {day}
            </button>
        {/each}
    </div>
</div>
