<script lang="ts">
    import {
        ArrowLeft01Icon,
        ArrowRight01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { CalendarMarker } from '@/types/app-dashboard';

    let {
        markers = [],
        month: initialMonth,
        year: initialYear,
    }: {
        markers?: CalendarMarker[];
        month?: number;
        year?: number;
    } = $props();

    const days = ['أحد', 'إثن', 'ثلا', 'أرب', 'خمي', 'جمع', 'سبت'];
    const now = new Date();
    let month = $state(initialMonth ?? now.getMonth());
    let year = $state(initialYear ?? now.getFullYear());

    const monthLabel = $derived(
        new Date(year, month, 1).toLocaleDateString('ar-SA', {
            month: 'long',
            year: 'numeric',
        }),
    );

    const daysInMonth = $derived(new Date(year, month + 1, 0).getDate());
    const firstWeekday = $derived(new Date(year, month, 1).getDay());
    const monthDays = $derived(
        Array.from({ length: daysInMonth }, (_, i) => i + 1),
    );
    const leadingBlanks = $derived(
        Array.from({ length: firstWeekday }, (_, i) => i),
    );

    const markerDates = $derived(new Set(markers.map((marker) => marker.date)));

    const today = $derived(now);
    const isToday = (day: number) =>
        today.getFullYear() === year &&
        today.getMonth() === month &&
        today.getDate() === day;

    function prevMonth() {
        if (month === 0) {
            month = 11;
            year -= 1;
        } else {
            month -= 1;
        }
    }

    function nextMonth() {
        if (month === 11) {
            month = 0;
            year += 1;
        } else {
            month += 1;
        }
    }

    function dayKey(day: number): string {
        const m = String(month + 1).padStart(2, '0');
        const d = String(day).padStart(2, '0');

        return `${year}-${m}-${d}`;
    }
</script>

<div class="th-card p-4">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold" style="color: var(--th-text)">
            {monthLabel}
        </h3>
        <div class="flex gap-1">
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded-lg th-hover"
                aria-label="الشهر السابق"
                onclick={prevMonth}
            >
                <HugeiconsIcon
                    icon={ArrowRight01Icon}
                    size={16}
                    style="color: var(--th-text-muted)"
                />
            </button>
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded-lg th-hover"
                aria-label="الشهر التالي"
                onclick={nextMonth}
            >
                <HugeiconsIcon
                    icon={ArrowLeft01Icon}
                    size={16}
                    style="color: var(--th-text-muted)"
                />
            </button>
        </div>
    </div>

    <div
        class="grid grid-cols-7 gap-1 text-center text-xs"
        style="color: var(--th-text-muted)"
    >
        {#each days as day (day)}
            <span class="py-1 font-medium">{day}</span>
        {/each}
        {#each leadingBlanks as blank (blank)}
            <span></span>
        {/each}
        {#each monthDays as day (day)}
            {@const hasMarker = markerDates.has(dayKey(day))}
            <button
                type="button"
                class="relative flex size-8 items-center justify-center rounded-full text-sm transition-colors
                    {isToday(day)
                    ? 'th-btn-primary font-semibold text-white'
                    : 'th-hover'}"
                style={!isToday(day) ? 'color: var(--th-text)' : undefined}
            >
                {day}
                {#if hasMarker && !isToday(day)}
                    <span
                        class="absolute bottom-0.5 size-1 rounded-full"
                        style="background: var(--th-primary)"
                    ></span>
                {/if}
            </button>
        {/each}
    </div>
</div>
