<script lang="ts">
    import {
        Add01Icon,
        ArrowLeft01Icon,
        Delete02Icon,
        Image01Icon,
        TextAlignCenterIcon,
        TextAlignLeft01Icon,
        TextAlignRight01Icon,
        ViewIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import {
        index as templatesIndex,
        store as storeTemplate,
        update as updateTemplate,
        preview as previewTemplate,
    } from '@/actions/App/Http/Controllers/CertificateTemplateController';
    import AppHead from '@/components/AppHead.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubRef } from '@/types';

    type CatalogEntry = {
        value: string;
        label: string;
        is_image: boolean;
        is_static: boolean;
    };

    type Field = {
        binding: string;
        static_text: string;
        x: number;
        y: number;
        width: number;
        font_size: number;
        font_family: string;
        font_weight: 'normal' | 'bold';
        color: string;
        align: 'left' | 'center' | 'right';
    };

    type TemplateData = {
        id: number;
        name: string;
        status: string;
        is_default: boolean;
        image_url: string | null;
        width: number;
        height: number;
        fields: Array<
            Omit<Field, 'static_text'> & { static_text: string | null }
        >;
    };

    let {
        club,
        fieldCatalog = [],
        template = null,
        mode = 'create',
    }: {
        club: ClubRef;
        fieldCatalog?: CatalogEntry[];
        template?: TemplateData | null;
        mode?: 'create' | 'edit';
    } = $props();

    const isEdit = $derived(mode === 'edit');

    const catalogByValue = $derived(
        new Map(fieldCatalog.map((entry) => [entry.value, entry])),
    );
    const catalogOptions = $derived(
        fieldCatalog.map((entry) => ({
            value: entry.value,
            label: entry.label,
        })),
    );

    const form = useForm({
        name: template?.name ?? '',
        status: template?.status ?? 'draft',
        image: null as File | null,
    });

    let fields = $state<Field[]>(
        (template?.fields ?? []).map((field) => ({
            ...field,
            static_text: field.static_text ?? '',
            font_weight: field.font_weight as 'normal' | 'bold',
            align: field.align as 'left' | 'center' | 'right',
        })),
    );
    let selectedIndex = $state<number | null>(null);
    let addBinding = $state<string>(fieldCatalog[0]?.value ?? '');

    // Background image: an existing URL (edit) and/or a newly chosen file held
    // on the form so its validation errors are typed.
    let existingImageUrl = $state<string | null>(template?.image_url ?? null);
    let newImageUrl = $state<string | null>(null);
    const backgroundUrl = $derived(newImageUrl ?? existingImageUrl);

    $effect(() => {
        const file = form.image;

        if (!file) {
            newImageUrl = null;

            return;
        }

        const url = URL.createObjectURL(file);
        newImageUrl = url;

        return () => URL.revokeObjectURL(url);
    });

    // The canvas's rendered pixel height drives font-size preview (font_size is
    // stored as a fraction of the template height).
    let canvasEl = $state<HTMLDivElement>();
    let canvasHeight = $state(0);

    const selected = $derived(
        selectedIndex !== null ? (fields[selectedIndex] ?? null) : null,
    );
    const selectedEntry = $derived(
        selected ? (catalogByValue.get(selected.binding) ?? null) : null,
    );

    function handleImageChange(event: Event): void {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;

        if (file) {
            form.image = file;
        }
    }

    function addField(): void {
        if (!addBinding) {
            return;
        }

        fields = [
            ...fields,
            {
                binding: addBinding,
                static_text: '',
                x: 0.35,
                y: 0.45,
                width: 0.3,
                font_size: 0.04,
                font_family: 'DejaVu Sans',
                font_weight: 'normal',
                color: '#000000',
                align: 'center',
            },
        ];
        selectedIndex = fields.length - 1;
    }

    function removeField(index: number): void {
        fields = fields.filter((_, i) => i !== index);
        selectedIndex = null;
    }

    function fieldLabel(field: Field): string {
        const entry = catalogByValue.get(field.binding);

        if (entry?.is_static) {
            return field.static_text || (entry?.label ?? field.binding);
        }

        return entry?.label ?? field.binding;
    }

    function clamp(value: number, min: number, max: number): number {
        return Math.min(max, Math.max(min, value));
    }

    // --- Drag & resize (native pointer events; positions stored as fractions) ---
    type DragState = {
        index: number;
        mode: 'move' | 'resize';
        pointerStartX: number;
        pointerStartY: number;
        originX: number;
        originY: number;
        originWidth: number;
    };

    let drag: DragState | null = null;

    function onPointerDown(
        event: PointerEvent,
        index: number,
        dragMode: 'move' | 'resize',
    ): void {
        event.preventDefault();
        event.stopPropagation();
        selectedIndex = index;

        const field = fields[index];
        drag = {
            index,
            mode: dragMode,
            pointerStartX: event.clientX,
            pointerStartY: event.clientY,
            originX: field.x,
            originY: field.y,
            originWidth: field.width,
        };

        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
    }

    function onPointerMove(event: PointerEvent): void {
        if (!drag || !canvasEl) {
            return;
        }

        const rect = canvasEl.getBoundingClientRect();
        const dx = (event.clientX - drag.pointerStartX) / rect.width;
        const dy = (event.clientY - drag.pointerStartY) / rect.height;
        const field = fields[drag.index];

        if (!field) {
            return;
        }

        if (drag.mode === 'move') {
            field.x = clamp(drag.originX + dx, 0, Math.max(0, 1 - field.width));
            field.y = clamp(drag.originY + dy, 0, 0.99);
        } else {
            field.width = clamp(drag.originWidth + dx, 0.05, 1 - field.x);
        }
    }

    function onPointerUp(): void {
        drag = null;
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
    }

    function previewUrl(): string | null {
        if (!isEdit || !template) {
            return null;
        }

        return previewTemplate({ club: club.id, template: template.id }).url;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        const serialized = fields.map((field) => ({
            binding: field.binding,
            static_text: catalogByValue.get(field.binding)?.is_static
                ? field.static_text
                : null,
            x: field.x,
            y: field.y,
            width: field.width,
            font_size: field.font_size,
            font_family: field.font_family,
            font_weight: field.font_weight,
            color: field.color,
            align: field.align,
        }));

        form.transform((data) => {
            const payload: Record<string, unknown> = {
                ...data,
                fields: serialized,
            };

            // Omit the image key when no new file was chosen so the server keeps
            // the existing background on edit.
            if (!data.image) {
                delete payload.image;
            }

            if (isEdit) {
                payload._method = 'put';
            }

            return payload;
        });

        const url =
            isEdit && template
                ? updateTemplate({ club: club.id, template: template.id }).url
                : storeTemplate(club.id).url;

        form.post(url, { forceFormData: true });
    }
</script>

<AppHead
    title={isEdit
        ? t('certificate_templates.editor_title_edit')
        : t('certificate_templates.editor_title_create')}
/>

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <div class="flex flex-col gap-3">
            <Link
                href={templatesIndex(club.id).url}
                class="inline-flex w-fit items-center gap-1.5 text-[13px] text-[#7e7e7e] transition-colors hover:text-brand"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={ArrowLeft01Icon}
                    class="size-4 rtl:rotate-180"
                />
                {t('certificate_templates.title')}
            </Link>
            <h1 class="text-start text-xl text-[#5f5f5f] sm:text-2xl">
                {isEdit
                    ? t('certificate_templates.editor_title_edit')
                    : t('certificate_templates.editor_title_create')}
            </h1>
        </div>

        <form
            onsubmit={submit}
            class="flex flex-col gap-6 lg:flex-row lg:items-start"
        >
            <!-- Canvas -->
            <div class="flex flex-1 flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <span class="text-start text-[14px] text-[#7e7e7e]">
                        {t('certificate_templates.background_label')}
                    </span>
                    <label
                        class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-full bg-brand/15 px-5 py-2 text-[13px] text-brand transition-colors hover:bg-brand/25"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={Image01Icon}
                            class="size-4"
                        />
                        {t('certificate_templates.background_label')}
                        <input
                            type="file"
                            accept="image/png,image/jpeg"
                            onchange={handleImageChange}
                            class="hidden"
                        />
                    </label>
                    <span class="text-start text-[12px] text-[#7e7e7e]">
                        {t('certificate_templates.background_hint')}
                    </span>
                    <InputError message={form.errors.image} />
                </div>

                {#if backgroundUrl}
                    <div
                        bind:this={canvasEl}
                        bind:clientHeight={canvasHeight}
                        class="relative w-full touch-none overflow-hidden rounded-[12px] border border-black/10 bg-white select-none"
                    >
                        <img
                            src={backgroundUrl}
                            alt=""
                            class="block w-full"
                            draggable="false"
                        />

                        {#each fields as field, index (index)}
                            {@const entry = catalogByValue.get(field.binding)}
                            <div
                                role="button"
                                tabindex="0"
                                onpointerdown={(e) =>
                                    onPointerDown(e, index, 'move')}
                                class="absolute cursor-move border border-dashed {selectedIndex ===
                                index
                                    ? 'border-brand bg-brand/10'
                                    : 'border-brand/40 hover:border-brand'}"
                                style="left: {field.x * 100}%; top: {field.y *
                                    100}%; width: {field.width * 100}%;"
                            >
                                {#if entry?.is_image}
                                    <div
                                        class="flex items-center justify-center gap-1 py-2 text-[11px] text-brand"
                                    >
                                        <HugeiconsIcon
                                            strokeWidth={2}
                                            icon={Image01Icon}
                                            class="size-3.5"
                                        />
                                        {entry.label}
                                    </div>
                                {:else}
                                    <span
                                        class="block w-full leading-tight"
                                        style="font-size: {Math.max(
                                            8,
                                            field.font_size * canvasHeight,
                                        )}px; font-weight: {field.font_weight}; color: {field.color}; text-align: {field.align};"
                                    >
                                        {fieldLabel(field)}
                                    </span>
                                {/if}

                                <!-- width resize handle -->
                                <span
                                    role="button"
                                    tabindex="-1"
                                    aria-label="resize"
                                    onpointerdown={(e) =>
                                        onPointerDown(e, index, 'resize')}
                                    class="absolute -end-1.5 top-1/2 size-3 -translate-y-1/2 cursor-ew-resize rounded-full border border-white bg-brand"
                                ></span>
                            </div>
                        {/each}
                    </div>
                {:else}
                    <div
                        class="flex aspect-[1.414/1] w-full items-center justify-center rounded-[12px] border border-dashed border-black/20 bg-white text-center text-[13px] text-[#7e7e7e]"
                    >
                        {t('certificate_templates.upload_first')}
                    </div>
                {/if}
            </div>

            <!-- Side panel -->
            <div class="flex w-full shrink-0 flex-col gap-5 lg:w-80">
                <div
                    class="flex flex-col gap-4 rounded-[16px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.06)]"
                >
                    <div class="flex flex-col gap-2">
                        <label
                            for="tpl-name"
                            class="text-start text-[14px] text-[#7e7e7e]"
                        >
                            {t('certificate_templates.name_label')}
                            <span class="text-[#f13e3e]">*</span>
                        </label>
                        <input
                            id="tpl-name"
                            type="text"
                            required
                            bind:value={form.name}
                            placeholder={t(
                                'certificate_templates.name_placeholder',
                            )}
                            class="h-10 rounded-[10px] border border-black/20 bg-white px-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-start text-[14px] text-[#7e7e7e]">
                            {t('certificate_templates.status_label')}
                        </span>
                        <FilterSelect
                            class="min-h-10 h-10 rounded-[10px] border-black/20 px-4 text-[13px] text-black"
                            ariaLabel={t('certificate_templates.status_label')}
                            bind:value={form.status}
                            options={[
                                {
                                    value: 'draft',
                                    label: t(
                                        'certificate_templates.status_draft',
                                    ),
                                },
                                {
                                    value: 'active',
                                    label: t(
                                        'certificate_templates.status_active',
                                    ),
                                },
                            ]}
                        />
                    </div>
                </div>

                <!-- Add field -->
                <div
                    class="flex flex-col gap-3 rounded-[16px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.06)]"
                >
                    <span class="text-start text-[14px] text-[#7e7e7e]">
                        {t('certificate_templates.add_field')}
                    </span>
                    <div class="flex items-center gap-2">
                        <FilterSelect
                            class="min-h-10 h-10 flex-1 rounded-[10px] border-black/20 px-4 text-[13px] text-black"
                            ariaLabel={t('certificate_templates.add_field')}
                            bind:value={addBinding}
                            options={catalogOptions}
                        />
                        <button
                            type="button"
                            onclick={addField}
                            class="inline-flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-brand text-white transition-colors hover:bg-brand-dark"
                            aria-label={t('certificate_templates.add_field')}
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={Add01Icon}
                                class="size-4"
                            />
                        </button>
                    </div>
                </div>

                <!-- Selected field editor -->
                {#if selected && selectedIndex !== null}
                    <div
                        class="flex flex-col gap-4 rounded-[16px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.06)]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-start text-[14px] text-[#5f5f5f]">
                                {t('certificate_templates.selected_field')}
                            </span>
                            <button
                                type="button"
                                onclick={() => removeField(selectedIndex!)}
                                aria-label={t(
                                    'certificate_templates.remove_field',
                                )}
                                class="inline-flex size-7 items-center justify-center rounded-full bg-black/5 text-[#5f5f5f] transition-colors hover:bg-[#f13e3e] hover:text-white"
                            >
                                <HugeiconsIcon
                                    strokeWidth={2}
                                    icon={Delete02Icon}
                                    class="size-3.5"
                                />
                            </button>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-start text-[13px] text-[#7e7e7e]">
                                {t('certificate_templates.binding_label')}
                            </span>
                            <FilterSelect
                                class="min-h-10 h-10 rounded-[10px] border-black/20 px-4 text-[13px] text-black"
                                ariaLabel={t(
                                    'certificate_templates.binding_label',
                                )}
                                bind:value={selected.binding}
                                options={catalogOptions}
                            />
                        </div>

                        {#if selectedEntry?.is_static}
                            <div class="flex flex-col gap-2">
                                <span
                                    class="text-start text-[13px] text-[#7e7e7e]"
                                >
                                    {t(
                                        'certificate_templates.custom_text_label',
                                    )}
                                </span>
                                <input
                                    type="text"
                                    bind:value={selected.static_text}
                                    placeholder={t(
                                        'certificate_templates.custom_text_placeholder',
                                    )}
                                    class="h-10 rounded-[10px] border border-black/20 bg-white px-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                                />
                            </div>
                        {/if}

                        {#if !selectedEntry?.is_image}
                            <div class="flex flex-col gap-2">
                                <span
                                    class="text-start text-[13px] text-[#7e7e7e]"
                                >
                                    {t('certificate_templates.font_size_label')}
                                </span>
                                <input
                                    type="range"
                                    min="0.01"
                                    max="0.2"
                                    step="0.005"
                                    bind:value={selected.font_size}
                                    class="accent-brand"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <span
                                    class="text-start text-[13px] text-[#7e7e7e]"
                                >
                                    {t(
                                        'certificate_templates.font_weight_label',
                                    )}
                                </span>
                                <FilterSelect
                                    class="min-h-10 h-10 rounded-[10px] border-black/20 px-4 text-[13px] text-black"
                                    ariaLabel={t(
                                        'certificate_templates.font_weight_label',
                                    )}
                                    bind:value={selected.font_weight}
                                    options={[
                                        {
                                            value: 'normal',
                                            label: t(
                                                'certificate_templates.weight_normal',
                                            ),
                                        },
                                        {
                                            value: 'bold',
                                            label: t(
                                                'certificate_templates.weight_bold',
                                            ),
                                        },
                                    ]}
                                />
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-start text-[13px] text-[#7e7e7e]"
                                >
                                    {t('certificate_templates.color_label')}
                                </span>
                                <input
                                    type="color"
                                    bind:value={selected.color}
                                    class="h-9 w-14 cursor-pointer rounded-[8px] border border-black/20 bg-white"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <span
                                    class="text-start text-[13px] text-[#7e7e7e]"
                                >
                                    {t('certificate_templates.align_label')}
                                </span>
                                <div class="flex items-center gap-2">
                                    {#each [{ value: 'left', icon: TextAlignLeft01Icon }, { value: 'center', icon: TextAlignCenterIcon }, { value: 'right', icon: TextAlignRight01Icon }] as option (option.value)}
                                        <button
                                            type="button"
                                            onclick={() =>
                                                (selected.align =
                                                    option.value as
                                                        | 'left'
                                                        | 'center'
                                                        | 'right')}
                                            aria-label={option.value}
                                            class="inline-flex h-9 flex-1 items-center justify-center rounded-[8px] border {selected.align ===
                                            option.value
                                                ? 'border-brand bg-brand/10 text-brand'
                                                : 'border-black/15 text-[#7e7e7e]'}"
                                        >
                                            <HugeiconsIcon
                                                strokeWidth={2}
                                                icon={option.icon}
                                                class="size-4"
                                            />
                                        </button>
                                    {/each}
                                </div>
                            </div>
                        {/if}
                    </div>
                {:else}
                    <p
                        class="rounded-[16px] bg-white p-5 text-start text-[13px] text-[#7e7e7e] shadow-[8px_8px_48px_8px_rgba(0,0,0,0.06)]"
                    >
                        {t('certificate_templates.no_fields')}
                    </p>
                {/if}

                <!-- Actions -->
                {#if form.hasErrors}
                    <p class="text-start text-[12px] text-[#f13e3e]">
                        {t('certificate_templates.has_errors')}
                    </p>
                {/if}
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        disabled={form.processing}
                        class="inline-flex flex-1 items-center justify-center rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {form.processing
                            ? t('certificate_templates.saving')
                            : t('certificate_templates.save')}
                    </button>
                    {#if previewUrl()}
                        <a
                            href={previewUrl()}
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-1.5 rounded-full bg-brand/15 px-5 py-2.5 text-[13px] text-brand transition-colors hover:bg-brand/25"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={ViewIcon}
                                class="size-4"
                            />
                            {t('certificate_templates.preview')}
                        </a>
                    {/if}
                </div>
            </div>
        </form>
    </div>
</div>
