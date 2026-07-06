<script lang="ts">
    import DemoRolePicker from '@/components/app/DemoRolePicker.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { t } from '@/lib/i18n.svelte';
    import '../../../css/app-shell.css';
</script>

<AppHead title={t('dashboard.entry_title')} />

<div
    class="app-shell relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10"
    style="background: #fdfdfd"
>
    <div
        class="pointer-events-none absolute rounded-full"
        style="left: 27%; top: 34%; width: 420px; height: 420px; background: rgba(200,146,74,0.12); filter: blur(48px)"
    ></div>
    <div
        class="pointer-events-none absolute rounded-full"
        style="right: 8%; top: 8%; width: 180px; height: 180px; background: rgba(200,146,74,0.2); filter: blur(32px)"
    ></div>

    <div class="relative z-10 w-full max-w-2xl">
        <div class="mb-8 flex flex-col items-center text-center">
            <AppLogoIcon class="mb-4 size-14" />
            <h1 class="text-2xl font-bold text-black">
                {t('dashboard.entry_title')}
            </h1>
            <p class="mt-2 max-w-md text-sm text-[#7e7e7e]">
                {t('dashboard.entry_subtitle')}
            </p>
        </div>

        <DemoRolePicker variant="toggles" />
    </div>
</div>
