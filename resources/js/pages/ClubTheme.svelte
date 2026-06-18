<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import BackLink from '@/components/BackLink.svelte';
    import ClubThemeForm from '@/components/ClubThemeForm.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubBranding } from '@/types';

    let {
        club,
        logoUrl = null,
    }: {
        club: ClubBranding;
        logoUrl?: string | null;
    } = $props();
</script>

<AppHead title="{t('theme.title')} — {club.name}" />

<div
    class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
>
    <BackLink
        href={`/clubs/${club.id}/manage`}
        label={t('app.back_to_manage')}
    />

    <!-- Header banner -->
    <section
        class="relative overflow-hidden rounded-[28px] px-6 py-10 text-start shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:px-10"
        style="background-color: {club.theme ?? '#006471'};"
    >
        <div class="relative z-10">
            <p class="text-[24px] leading-tight text-white sm:text-[30px]">
                {t('theme.title')}
            </p>
            <p class="mt-2 text-sm text-white/75">
                {club.name} — {t('theme.subtitle')}
            </p>
        </div>
    </section>

    <!-- Editor (shared with the supervisor dashboard) -->
    <section
        class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:p-8"
    >
        <ClubThemeForm {club} {logoUrl} showPreview />
    </section>
</div>
