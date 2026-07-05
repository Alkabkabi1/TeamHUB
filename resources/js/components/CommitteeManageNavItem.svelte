<script lang="ts">
    import { TaskDone01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import { manage } from '@/routes/projects';
    import type { ManagedProject } from '@/types';

    let { onNavigate }: { onNavigate?: () => void } = $props();
    type Direction = 'rtl' | 'ltr';

    const managedProjects = $derived(
        (page.props.auth?.user?.managed_projects ?? []) as ManagedProject[],
    );
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );
    const url = currentUrlState();

    const isActive = $derived(
        managedProjects.some((project) =>
            url.isCurrentUrl(
                manage({
                    workspace: project.workspace_id,
                    project: project.id,
                }),
                url.currentUrl,
            ),
        ),
    );

    const linkClass =
        'group flex w-full cursor-pointer items-center gap-4 rounded-full px-3 py-2 leading-none transition-colors hover:bg-brand/5';
    const labelClass = $derived(
        `flex-1 text-start text-sm transition-colors group-hover:text-brand ${
            isActive ? 'font-bold text-brand' : 'text-black'
        }`,
    );
    const iconClass = $derived(
        `size-4 shrink-0 transition-colors group-hover:text-brand ${
            isActive ? 'text-brand' : 'text-black'
        }`,
    );
</script>

{#if managedProjects.length === 1}
    <Link
        href={toUrl(
            manage({
                workspace: managedProjects[0].workspace_id,
                project: managedProjects[0].id,
            }),
        )}
        onclick={onNavigate}
        class={linkClass}
    >
        <span class={labelClass}>{t('nav.committee_leader_dashboard')}</span>
        <HugeiconsIcon
            strokeWidth={2}
            icon={TaskDone01Icon}
            class={iconClass}
        />
    </Link>
{:else if managedProjects.length > 1}
    <DropdownMenu>
        <DropdownMenuTrigger class={linkClass}>
            <span class={labelClass}>{t('nav.committee_leader_dashboard')}</span
            >
            <HugeiconsIcon
                strokeWidth={2}
                icon={TaskDone01Icon}
                class={iconClass}
            />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" dir={direction} class="min-w-52">
            {#each managedProjects as project (project.id)}
                <DropdownMenuItem>
                    {#snippet child({ props })}
                        <Link
                            href={toUrl(
                                manage({
                                    workspace: project.workspace_id,
                                    project: project.id,
                                }),
                            )}
                            onclick={onNavigate}
                            dir={direction}
                            class="w-full text-start"
                            {...props}
                        >
                            {project.name}
                        </Link>
                    {/snippet}
                </DropdownMenuItem>
            {/each}
        </DropdownMenuContent>
    </DropdownMenu>
{/if}
