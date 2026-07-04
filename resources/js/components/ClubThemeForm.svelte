<script lang="ts">
    import { ColorPickerIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { useForm } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubBranding } from '@/types';

    /**
     * Shared club theme editor (color + logo) posting to PUT /clubs/{id}/theme.
     * Reused by the standalone ClubTheme page (with live preview) and inline in
     * the supervisor dashboard (compact, with a cancel button) — Figma 57:1721.
     */
    let {
        club,
        logoUrl = null,
        showPreview = false,
        onCancel,
    }: {
        club: ClubBranding;
        logoUrl?: string | null;
        showPreview?: boolean;
        onCancel?: () => void;
    } = $props();

    const SWATCHES = [
        '#006471',
        '#1a56db',
        '#7e3af2',
        '#e74694',
        '#ff5a1f',
        '#0e9f6e',
        '#c27803',
        '#1f2937',
    ];

    const initialColor = club.theme ?? '#006471';

    const form = useForm({
        name: club.name,
        theme: initialColor,
        logo: null as File | null,
    });

    // Local state for live preview / swatch highlighting.
    let previewColor = $state(initialColor);
    let previewLogoUrl = $state<string | null>(logoUrl ?? null);

    function handleColorInput(e: Event): void {
        const input = e.target as HTMLInputElement;
        previewColor = input.value;
        form.theme = input.value;
    }

    function handleSwatchClick(color: string): void {
        previewColor = color;
        form.theme = color;
    }

    function handleLogoChange(e: Event): void {
        const input = e.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        form.logo = file;

        if (file) {
            if (previewLogoUrl && previewLogoUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewLogoUrl);
            }

            previewLogoUrl = URL.createObjectURL(file);
        }
    }

    function removeLogo(): void {
        form.logo = null;
        previewLogoUrl = null;
    }

    function reset(): void {
        previewColor = initialColor;
        form.name = club.name;
        form.theme = initialColor;
        previewLogoUrl = logoUrl ?? null;
        form.logo = null;
        onCancel?.();
    }

    function submit(e: SubmitEvent): void {
        e.preventDefault();
        form.put(`/clubs/${club.id}/theme`, {
            forceFormData: true,
            preserveScroll: true,
        });
    }
</script>

{#if showPreview}
    <section
        aria-label={t('theme.preview_heading')}
        class="mb-6 rounded-[16px] p-5 text-start"
        style="background-color: {previewColor};"
    >
        <div class="flex items-center gap-4">
            <div
                class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20"
            >
                {#if previewLogoUrl}
                    <img
                        src={previewLogoUrl}
                        alt={club.name}
                        class="h-full w-full object-cover"
                    />
                {:else}
                    <span class="text-2xl font-bold text-white">
                        {form.name.charAt(0)}
                    </span>
                {/if}
            </div>
            <div class="flex-1 text-start">
                <p class="text-lg font-semibold text-white">{form.name}</p>
                <p class="mt-1 text-sm text-white/75">
                    {t('theme.subtitle')}
                </p>
            </div>
        </div>
    </section>
{/if}

<form onsubmit={submit} class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-2 lg:col-span-2">
            <label
                for="workspace-name"
                class="text-start text-[14px] text-[#7e7e7e]"
            >
                {t('app.workspace')}
            </label>
            <input
                id="workspace-name"
                name="name"
                bind:value={form.name}
                class="h-10 rounded-[10px] border border-black/20 bg-white px-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
            />
            <InputError message={form.errors.name} />
        </div>

        <!-- Primary color -->
        <div class="flex flex-col gap-2">
            <label
                for="theme-color"
                class="text-start text-[14px] text-[#7e7e7e]"
            >
                {t('dashboard_supervisor.theme_primary_color')}
            </label>
            <div
                class="flex h-10 items-center gap-3 rounded-[10px] border border-black/20 bg-white px-4"
            >
                <span class="text-[12px] text-[#5f5f5f]">{previewColor}</span>
                <div
                    class="flex flex-1 flex-wrap items-center justify-center gap-2"
                    aria-label={t('theme.swatches_label')}
                >
                    {#each SWATCHES as swatch (swatch)}
                        <button
                            type="button"
                            class="size-5 rounded-full border transition-transform hover:scale-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                            style="background-color: {swatch}; border-color: {previewColor ===
                            swatch
                                ? '#000'
                                : 'transparent'}; outline-color: {swatch};"
                            aria-label={t('dashboard_supervisor.pick_color', {
                                color: swatch,
                            })}
                            aria-pressed={previewColor === swatch}
                            onclick={() => handleSwatchClick(swatch)}
                        ></button>
                    {/each}
                </div>
                <label class="relative flex cursor-pointer items-center">
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={ColorPickerIcon}
                        class="size-4 text-[#7e7e7e]"
                    />
                    <input
                        id="theme-color"
                        name="theme"
                        type="color"
                        value={previewColor}
                        oninput={handleColorInput}
                        class="absolute inset-0 cursor-pointer opacity-0"
                    />
                </label>
            </div>
            <InputError message={form.errors.theme} />
        </div>

        <!-- Logo upload -->
        <div class="flex flex-col gap-2">
            <label
                for="logo-upload"
                class="text-start text-[14px] text-[#7e7e7e]"
            >
                {t('dashboard_supervisor.upload_logo')}
            </label>
            <div
                class="flex h-10 items-center gap-3 rounded-[10px] border border-black/20 bg-white px-4"
            >
                {#if previewLogoUrl}
                    <img
                        src={previewLogoUrl}
                        alt={club.name}
                        class="size-6 rounded-full border border-black/10 object-cover"
                    />
                    <button
                        type="button"
                        class="text-[11px] text-[#f13e3e] underline"
                        onclick={removeLogo}
                    >
                        {t('theme.logo_remove')}
                    </button>
                {/if}
                <input
                    id="logo-upload"
                    name="logo"
                    type="file"
                    accept="image/png,image/jpeg,image/jpg"
                    onchange={handleLogoChange}
                    class="min-w-0 flex-1 text-start text-[12px] text-[#5f5f5f] file:me-3 file:rounded-full file:border-0 file:bg-brand/10 file:px-3 file:py-1 file:text-[11px] file:text-brand"
                />
            </div>
            <InputError message={form.errors.logo} />
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-start gap-3">
        <button
            type="submit"
            disabled={form.processing}
            class="cursor-pointer rounded-full bg-brand px-8 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
        >
            {form.processing ? t('theme.saving') : t('theme.save')}
        </button>
        {#if onCancel}
            <button
                type="button"
                onclick={reset}
                class="cursor-pointer rounded-full bg-black/5 px-6 py-2.5 text-[12px] text-[#5f5f5f] transition-colors hover:bg-black/10"
            >
                {t('members.cancel')}
            </button>
        {/if}
    </div>
</form>
