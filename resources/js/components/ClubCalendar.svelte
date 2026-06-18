<script lang="ts">
    /**
     * Month-grid calendar for a club, powered by the club's real events. Days
     * that have events are highlighted and link through to the event. Month
     * navigation happens entirely on the client over the supplied event list.
     * Weekday/month names and day numbers are localized, and the grid flows
     * with the document direction (Sunday on the right in RTL).
     */
    import {
        ArrowLeft01Icon,
        ArrowRight01Icon,
        Calendar03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import {
        HoverCard,
        HoverCardContent,
        HoverCardTrigger,
    } from '@/components/ui/hover-card';
    import {
        currentLocale,
        formatDate,
        formatNumber,
        t,
    } from '@/lib/i18n.svelte';

    type CalendarEvent = {
        id: number;
        title: string;
        starts_at: string;
    };

    let { events = [] }: { events?: CalendarEvent[] } = $props();

    const dayKey = (year: number, month: number, day: number): string =>
        `${year}-${month}-${day}`;

    const today = new Date();
    const todayKey = dayKey(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    );

    // Start on the month of the next upcoming event when there is one, so the
    // calendar opens on something meaningful; otherwise the current month.
    const initial = (() => {
        const upcoming = events
            .map((event) => new Date(event.starts_at))
            .filter((date) => date.getTime() >= today.getTime())
            .sort((a, b) => a.getTime() - b.getTime())[0];
        const anchor = upcoming ?? today;

        return { year: anchor.getFullYear(), month: anchor.getMonth() };
    })();

    let viewYear = $state(initial.year);
    let viewMonth = $state(initial.month);

    const isRtl = $derived(currentLocale() === 'ar');
    const prevIcon = $derived(isRtl ? ArrowRight01Icon : ArrowLeft01Icon);
    const nextIcon = $derived(isRtl ? ArrowLeft01Icon : ArrowRight01Icon);

    const eventsByDay = $derived.by(() => {
        const buckets: Record<string, CalendarEvent[]> = {};

        for (const event of events) {
            const date = new Date(event.starts_at);
            const key = dayKey(
                date.getFullYear(),
                date.getMonth(),
                date.getDate(),
            );

            (buckets[key] ??= []).push(event);
        }

        for (const dayEvents of Object.values(buckets)) {
            dayEvents.sort(
                (a, b) =>
                    new Date(a.starts_at).getTime() -
                    new Date(b.starts_at).getTime(),
            );
        }

        return buckets;
    });

    const eventTime = (value: string): string =>
        formatDate(value, { hour: 'numeric', minute: '2-digit' });

    const dayLabel = (day: number): string =>
        formatDate(new Date(viewYear, viewMonth, day), {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });

    const monthLabel = $derived(
        formatDate(new Date(viewYear, viewMonth, 1), {
            month: 'long',
            year: 'numeric',
        }),
    );

    // Short weekday names in locale order (Sunday → Saturday). 2023-01-01 was a
    // Sunday, so offsetting the day of month gives each successive weekday.
    const weekdayNames = $derived.by(() =>
        Array.from({ length: 7 }, (_, offset) =>
            formatDate(new Date(2023, 0, 1 + offset), { weekday: 'short' }),
        ),
    );

    const weeks = $derived.by(() => {
        const leadingBlanks = new Date(viewYear, viewMonth, 1).getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        const cells: (number | null)[] = Array.from(
            { length: leadingBlanks },
            () => null,
        );

        for (let day = 1; day <= daysInMonth; day += 1) {
            cells.push(day);
        }

        while (cells.length % 7 !== 0) {
            cells.push(null);
        }

        const grid: (number | null)[][] = [];

        for (let index = 0; index < cells.length; index += 7) {
            grid.push(cells.slice(index, index + 7));
        }

        return grid;
    });

    function goToPreviousMonth(): void {
        if (viewMonth === 0) {
            viewMonth = 11;
            viewYear -= 1;
        } else {
            viewMonth -= 1;
        }
    }

    function goToNextMonth(): void {
        if (viewMonth === 11) {
            viewMonth = 0;
            viewYear += 1;
        } else {
            viewMonth += 1;
        }
    }
</script>

{#snippet cell(day: number, dayEvents: CalendarEvent[])}
    {@const isToday = dayKey(viewYear, viewMonth, day) === todayKey}
    <span
        class="text-[12px] sm:text-[14px] {isToday
            ? 'font-semibold text-white'
            : 'text-[#5f5f5f]'}"
    >
        {formatNumber(day)}
    </span>
    {#if dayEvents.length > 0}
        <span
            class="mt-auto flex items-center gap-1 text-[10px] {isToday
                ? 'text-white'
                : 'text-brand'}"
        >
            <span
                class="size-1.5 rounded-full {isToday
                    ? 'bg-white'
                    : 'bg-brand'}"
            ></span>
            {#if dayEvents.length > 1}
                <span>{formatNumber(dayEvents.length)}</span>
            {/if}
        </span>
    {/if}
{/snippet}

{#snippet eventList(day: number, dayEvents: CalendarEvent[])}
    <p class="px-1 text-start text-xs font-medium text-[#7e7e7e]">
        {dayLabel(day)}
    </p>
    <div class="mt-2 flex max-h-64 flex-col gap-0.5 overflow-y-auto">
        {#each dayEvents as event (event.id)}
            <Link
                href={`/events/${event.id}`}
                class="group flex items-start gap-2 rounded-lg p-2 transition-colors hover:bg-brand/5"
            >
                <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand"
                ></span>
                <span class="flex-1 text-start">
                    <span
                        class="block text-sm font-medium text-[#1f1f1f] group-hover:text-brand"
                    >
                        {event.title}
                    </span>
                    <span class="block text-xs text-[#7e7e7e]">
                        {eventTime(event.starts_at)}
                    </span>
                </span>
            </Link>
        {/each}
    </div>
{/snippet}

<div
    class="rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:p-6"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1.5">
            <button
                type="button"
                onclick={goToPreviousMonth}
                aria-label={t('club.calendar_prev')}
                class="flex size-9 cursor-pointer items-center justify-center rounded-full text-[#5f5f5f] transition-colors hover:bg-brand/10 hover:text-brand"
            >
                <HugeiconsIcon strokeWidth={2} icon={prevIcon} class="size-5" />
            </button>
            <button
                type="button"
                onclick={goToNextMonth}
                aria-label={t('club.calendar_next')}
                class="flex size-9 cursor-pointer items-center justify-center rounded-full text-[#5f5f5f] transition-colors hover:bg-brand/10 hover:text-brand"
            >
                <HugeiconsIcon strokeWidth={2} icon={nextIcon} class="size-5" />
            </button>
        </div>

        <div class="flex items-center gap-2 text-[#5f5f5f]">
            <span class="text-base font-medium sm:text-lg">{monthLabel}</span>
            <HugeiconsIcon
                strokeWidth={2}
                icon={Calendar03Icon}
                class="size-6 text-brand"
            />
        </div>
    </div>

    <div class="mt-6 grid grid-cols-7 gap-1.5 sm:gap-2.5">
        {#each weekdayNames as name (name)}
            <div
                class="pb-2 text-center text-[11px] text-[#7e7e7e] sm:text-[13px]"
            >
                {name}
            </div>
        {/each}

        {#each weeks as week, weekIndex (weekIndex)}
            {#each week as day, dayIndex (dayIndex)}
                {#if day === null}
                    <div class="aspect-square"></div>
                {:else}
                    {@const dayEvents =
                        eventsByDay[dayKey(viewYear, viewMonth, day)] ?? []}
                    {@const isToday =
                        dayKey(viewYear, viewMonth, day) === todayKey}
                    {@const baseClass =
                        'flex aspect-square flex-col items-start rounded-[12px] border p-2 text-start transition-colors'}
                    {@const toneClass = isToday
                        ? 'border-transparent bg-brand'
                        : dayEvents.length > 0
                          ? 'border-brand/40 bg-brand/5'
                          : 'border-black/5'}
                    {#if dayEvents.length > 0}
                        <HoverCard openDelay={150} closeDelay={100}>
                            <HoverCardTrigger>
                                {#snippet child({ props })}
                                    <Link
                                        href={`/events/${dayEvents[0].id}`}
                                        aria-label={dayEvents
                                            .map((event) => event.title)
                                            .join('، ')}
                                        class="{baseClass} {toneClass} cursor-pointer hover:border-brand hover:bg-brand/10"
                                        {...props}
                                    >
                                        {@render cell(day, dayEvents)}
                                    </Link>
                                {/snippet}
                            </HoverCardTrigger>
                            <HoverCardContent
                                dir={isRtl ? 'rtl' : 'ltr'}
                                align="center"
                                sideOffset={8}
                                class="w-72"
                            >
                                {@render eventList(day, dayEvents)}
                            </HoverCardContent>
                        </HoverCard>
                    {:else}
                        <div class="{baseClass} {toneClass}">
                            {@render cell(day, dayEvents)}
                        </div>
                    {/if}
                {/if}
            {/each}
        {/each}
    </div>
</div>
