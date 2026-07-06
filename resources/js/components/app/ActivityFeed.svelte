<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        Comment01Icon,
        FileUploadIcon,
        UserAdd01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import type { DashboardActivity } from '@/types/app-dashboard';

    let { activities }: { activities: DashboardActivity[] } = $props();

    const icons: Record<DashboardActivity['type'], IconSvgElement> = {
        comment: Comment01Icon,
        complete: CheckmarkCircle01Icon,
        upload: FileUploadIcon,
        assign: UserAdd01Icon,
    };
</script>

<div class="th-card p-4">
    <h3 class="mb-4 text-sm font-semibold" style="color: var(--th-text)">
        آخر النشاطات
    </h3>
    <ul class="space-y-4">
        {#each activities as item (item.id)}
            <li class="flex gap-3">
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-medium"
                    style="background: color-mix(in srgb, var(--th-primary) 12%, transparent); color: var(--th-primary)"
                >
                    {item.initials}
                </span>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-sm leading-relaxed"
                        style="color: var(--th-text)"
                    >
                        <span class="font-medium">{item.user}</span>
                        <span style="color: var(--th-text-muted)">
                            {item.action}
                        </span>
                        <span class="font-medium">{item.target}</span>
                    </p>
                    <p
                        class="mt-0.5 text-xs"
                        style="color: var(--th-text-muted)"
                    >
                        {item.time}
                    </p>
                </div>
                <HugeiconsIcon
                    icon={icons[item.type]}
                    size={16}
                    class="shrink-0 opacity-50"
                    style="color: var(--th-text-muted)"
                />
            </li>
        {/each}
    </ul>
</div>
