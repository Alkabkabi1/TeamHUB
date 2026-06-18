<script lang="ts">
    export const layout = () => null;

    import ProjectCard from '@/components/team-hub/ProjectCard.svelte';
    import { projects } from '@/components/team-hub/mock-data';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';
    import { Add01Icon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
</script>

<TeamHubLayout title="المشاريع — Team Hub" activePath="/preview/team-hub/projects">
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold" style="color: var(--th-text)">المشاريع</h1>
                <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
                    {projects.length} مشاريع نشطة في مساحة العمل
                </p>
            </div>
            <button type="button" class="th-btn-primary flex items-center gap-2 self-start rounded-xl px-4 py-2.5 text-sm font-medium">
                <HugeiconsIcon icon={Add01Icon} size={18} color="#fff" />
                مشروع جديد
            </button>
        </header>

        <div
            class="mb-6 flex max-w-md items-center gap-2 rounded-xl border px-4 py-2.5"
            style="background: var(--th-surface); border-color: var(--th-border)"
        >
            <HugeiconsIcon icon={Search01Icon} size={18} style="color: var(--th-text-muted)" />
            <input
                type="search"
                placeholder="بحث في المشاريع..."
                class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                style="color: var(--th-text)"
            />
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            {#each projects as project (project.id)}
                <ProjectCard {project} />
            {/each}
        </div>
    </div>
</TeamHubLayout>
