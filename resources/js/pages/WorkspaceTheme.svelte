<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import WorkspaceManageShell from '@/components/WorkspaceManageShell.svelte';
    import WorkspaceThemeForm from '@/components/WorkspaceThemeForm.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { WorkspaceBranding } from '@/types';

    let {
        workspace,
        logoUrl = null,
    }: {
        workspace: WorkspaceBranding;
        logoUrl?: string | null;
    } = $props();
</script>

<AppHead title={`${t('theme.title')} — ${workspace.name}`} />

<div
    class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
>
    <WorkspaceManageShell active="settings" {workspace} />

    <!-- Header banner -->
    <section
        class="relative overflow-hidden rounded-[28px] px-6 py-10 text-start shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:px-10"
        style="background-color: {workspace.theme ?? '#c8924a'};"
    >
        <div class="relative z-10">
            <p class="text-[24px] leading-tight text-white sm:text-[30px]">
                {t('theme.title')}
            </p>
            <p class="mt-2 text-sm text-white/75">
                {workspace.name} — {t('theme.subtitle')}
            </p>
        </div>
    </section>

    <!-- Editor (shared with the supervisor dashboard) -->
    <section
        class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:p-8"
    >
        <WorkspaceThemeForm {workspace} {logoUrl} showPreview />
    </section>
</div>
