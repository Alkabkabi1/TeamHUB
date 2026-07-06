<script lang="ts">
    import {
        ComputerIcon,
        Globe02Icon,
        Megaphone01Icon,
        SmartPhone01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import type { DashboardProject } from '@/types/app-dashboard';

    let { project }: { project: DashboardProject } = $props();

    const icons: Record<string, IconSvgElement> = {
        monitor: ComputerIcon,
        mobile: SmartPhone01Icon,
        web: Globe02Icon,
        megaphone: Megaphone01Icon,
    };
</script>

<Link href={project.url} class="block">
    <article
        class="th-card flex flex-col p-5 transition-transform hover:scale-[1.01]"
    >
        <div class="mb-3 flex items-start justify-between">
            <div
                class="flex size-10 items-center justify-center rounded-xl"
                style="background: color-mix(in srgb, {project.color} 15%, transparent)"
            >
                <HugeiconsIcon
                    icon={icons[project.icon] ?? ComputerIcon}
                    size={20}
                    color={project.color}
                />
            </div>
            <span class="text-sm font-semibold" style="color: {project.color}"
                >{project.progress}%</span
            >
        </div>

        <h3 class="font-semibold" style="color: var(--th-text)">
            {project.title}
        </h3>
        <p
            class="mt-1 line-clamp-2 text-sm"
            style="color: var(--th-text-muted)"
        >
            {project.description}
        </p>

        <div
            class="mt-4 h-1.5 overflow-hidden rounded-full"
            style="background: var(--th-border)"
        >
            <div
                class="h-full rounded-full transition-all"
                style="width: {project.progress}%; background: {project.color}"
            ></div>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <span class="text-xs" style="color: var(--th-text-muted)">
                {project.tasksCount} مهمة · {project.membersCount} أعضاء
            </span>
            <div class="flex -space-x-2 space-x-reverse">
                {#each project.members as member, i (member + i)}
                    <span
                        class="flex size-7 items-center justify-center rounded-full border-2 text-[10px] font-medium"
                        style="background: var(--th-surface); border-color: var(--th-card); color: var(--th-text-muted)"
                    >
                        {member}
                    </span>
                {/each}
            </div>
        </div>
    </article>
</Link>
