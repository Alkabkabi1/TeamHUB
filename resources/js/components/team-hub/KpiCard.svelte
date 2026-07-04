<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        Clock01Icon,
        Folder01Icon,
        TaskDone01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import type { HubKpi } from '@/types/team-hub';

    let { kpi }: { kpi: HubKpi } = $props();

    const icons: Record<HubKpi['icon'], IconSvgElement> = {
        projects: Folder01Icon,
        overdue: Clock01Icon,
        progress: TaskDone01Icon,
        done: CheckmarkCircle01Icon,
    };

    const iconColors: Record<HubKpi['icon'], string> = {
        projects: 'color: var(--th-primary)',
        overdue: 'color: var(--th-warning)',
        progress: 'color: var(--th-info)',
        done: 'color: var(--th-success)',
    };
</script>

<div class="th-card p-5">
    <div class="mb-3 flex items-start justify-between">
        <div
            class="flex size-10 items-center justify-center rounded-xl"
            style="background: color-mix(in srgb, var(--th-primary) 12%, transparent)"
        >
            <HugeiconsIcon
                icon={icons[kpi.icon]}
                size={20}
                style={iconColors[kpi.icon]}
            />
        </div>
        <span
            class="text-xs font-medium"
            style="color: {kpi.trendUp
                ? 'var(--th-success)'
                : 'var(--th-danger)'}"
        >
            {kpi.trend}
        </span>
    </div>
    <p class="text-2xl font-bold" style="color: var(--th-text)">{kpi.value}</p>
    <p class="mt-1 text-sm" style="color: var(--th-text-muted)">{kpi.label}</p>
</div>
