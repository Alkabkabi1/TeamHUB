<script lang="ts">
    import {
        Add01Icon,
        ArrowLeft01Icon,
        CheckmarkCircle02Icon,
        Delete02Icon,
        Edit02Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, router } from '@inertiajs/svelte';
    import {
        create as createTemplate,
        edit as editTemplate,
        destroy as destroyTemplate,
        setDefault as setDefaultTemplate,
    } from '@/actions/App/Http/Controllers/CertificateTemplateController';
    import { index as manageIndex } from '@/actions/App/Http/Controllers/ClubManagementController';
    import AppHead from '@/components/AppHead.svelte';
    import CertificateTemplatePreview from '@/components/CertificateTemplatePreview.svelte';
    import type { CertificateTemplateField } from '@/components/CertificateTemplatePreview.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubRef } from '@/types';

    type TemplateListItem = {
        id: number;
        name: string;
        status: string;
        is_default: boolean;
        image_url: string | null;
        width?: number;
        height?: number;
        fields_count: number;
        fields?: CertificateTemplateField[];
    };

    let {
        club,
        templates = [],
    }: {
        club: ClubRef;
        templates?: TemplateListItem[];
    } = $props();

    let deleteTarget = $state<TemplateListItem | null>(null);

    function confirmDelete(): void {
        if (!deleteTarget) {
            return;
        }

        router.delete(
            destroyTemplate({ club: club.id, template: deleteTarget.id }).url,
            {
                preserveScroll: true,
                onFinish: () => (deleteTarget = null),
            },
        );
    }

    function makeDefault(template: TemplateListItem): void {
        router.post(
            setDefaultTemplate({ club: club.id, template: template.id }).url,
            {},
            { preserveScroll: true },
        );
    }
</script>

<AppHead title={t('certificate_templates.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <div class="flex flex-col gap-3">
            <Link
                href={manageIndex(club.id).url}
                class="inline-flex w-fit items-center gap-1.5 text-[13px] text-[#7e7e7e] transition-colors hover:text-brand"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={ArrowLeft01Icon}
                    class="size-4 rtl:rotate-180"
                />
                {t('certificate_templates.back_to_manage')}
            </Link>

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-start text-xl text-[#5f5f5f] sm:text-2xl">
                        {t('certificate_templates.title')}
                    </h1>
                    <p class="max-w-2xl text-start text-[13px] text-[#7e7e7e]">
                        {t('certificate_templates.subtitle')}
                    </p>
                </div>

                <Link
                    href={createTemplate(club.id).url}
                    class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Add01Icon}
                        class="size-4"
                    />
                    {t('certificate_templates.new_template')}
                </Link>
            </div>
        </div>

        {#if templates.length === 0}
            <p
                class="rounded-[20px] bg-white p-8 text-center text-sm text-[#5f5f5f] shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                {t('certificate_templates.empty')}
            </p>
        {:else}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {#each templates as template (template.id)}
                    <div
                        class="flex flex-col overflow-hidden rounded-[20px] bg-white shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                    >
                        <CertificateTemplatePreview
                            imageUrl={template.image_url}
                            name={template.name}
                            width={template.width}
                            height={template.height}
                            isDefault={template.is_default}
                            status={template.status}
                            fields={template.fields}
                        />

                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="flex flex-col gap-1">
                                <p
                                    class="text-start text-[14px] font-medium text-black"
                                >
                                    {template.name}
                                </p>
                                <p
                                    class="text-start text-[12px] text-[#7e7e7e]"
                                >
                                    {t('certificate_templates.fields_count', {
                                        count: template.fields_count,
                                    })}
                                </p>
                            </div>

                            <div
                                class="mt-auto flex flex-wrap items-center gap-2"
                            >
                                <Link
                                    href={editTemplate({
                                        club: club.id,
                                        template: template.id,
                                    }).url}
                                    class="inline-flex items-center gap-1.5 rounded-full bg-brand/15 px-4 py-1.5 text-[12px] text-brand transition-colors hover:bg-brand/25"
                                >
                                    <HugeiconsIcon
                                        strokeWidth={2}
                                        icon={Edit02Icon}
                                        class="size-3.5"
                                    />
                                    {t('certificate_templates.edit')}
                                </Link>

                                {#if !template.is_default}
                                    <button
                                        type="button"
                                        onclick={() => makeDefault(template)}
                                        class="inline-flex items-center gap-1.5 rounded-full bg-black/5 px-4 py-1.5 text-[12px] text-[#5f5f5f] transition-colors hover:bg-black/10"
                                    >
                                        <HugeiconsIcon
                                            strokeWidth={2}
                                            icon={CheckmarkCircle02Icon}
                                            class="size-3.5"
                                        />
                                        {t('certificate_templates.set_default')}
                                    </button>
                                {/if}

                                <button
                                    type="button"
                                    onclick={() => (deleteTarget = template)}
                                    aria-label={t(
                                        'certificate_templates.delete',
                                    )}
                                    class="ms-auto inline-flex size-8 items-center justify-center rounded-full bg-black/5 text-[#5f5f5f] transition-colors hover:bg-[#f13e3e] hover:text-white"
                                >
                                    <HugeiconsIcon
                                        strokeWidth={2}
                                        icon={Delete02Icon}
                                        class="size-4"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</div>

<Dialog
    open={deleteTarget !== null}
    onOpenChange={(open) => !open && (deleteTarget = null)}
>
    <DialogContent>
        <DialogHeader>
            <DialogTitle
                >{t('certificate_templates.delete_confirm_title')}</DialogTitle
            >
            <DialogDescription>
                {t('certificate_templates.delete_confirm_body')}
            </DialogDescription>
        </DialogHeader>
        <DialogFooter>
            <button
                type="button"
                onclick={() => (deleteTarget = null)}
                class="rounded-full bg-black/5 px-6 py-2 text-[13px] text-[#5f5f5f] transition-colors hover:bg-black/10"
            >
                {t('certificate_templates.cancel')}
            </button>
            <button
                type="button"
                onclick={confirmDelete}
                class="rounded-full bg-[#f13e3e] px-6 py-2 text-[13px] text-white transition-colors hover:bg-[#d62f2f]"
            >
                {t('certificate_templates.delete')}
            </button>
        </DialogFooter>
    </DialogContent>
</Dialog>
