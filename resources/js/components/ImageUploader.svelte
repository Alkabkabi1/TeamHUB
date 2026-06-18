<script lang="ts">
    /**
     * Multi-image upload field shared by the Event and News forms.
     *
     * It manages three bindable pieces of state for the parent form:
     *  - `files`: newly selected File objects to upload (submit as `images[]`).
     *  - `existing`: media already attached to the record, still kept.
     *  - `removedIds`: ids of existing media the user removed (submit as
     *    `removed_media[]`).
     *
     * Removing an existing image moves its id into `removedIds`; removing a new
     * file just drops it from `files`. A live preview is shown for both.
     */
    import { Cancel01Icon, ImageAdd02Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { MediaImage } from '@/types';

    type Props = {
        files?: File[];
        existing?: MediaImage[];
        removedIds?: number[];
        label?: string;
        hint?: string;
        error?: string;
        max?: number;
        accept?: string;
    };

    let {
        files = $bindable([]),
        existing = $bindable([]),
        removedIds = $bindable([]),
        label,
        hint,
        error,
        max = 10,
        accept = 'image/jpeg,image/png,image/webp',
    }: Props = $props();

    let inputEl = $state<HTMLInputElement>();

    const total = $derived(existing.length + files.length);
    const canAddMore = $derived(total < max);

    // Object URLs for the freshly selected files. The effect revokes the
    // previous batch before creating a new one (and on unmount) to avoid leaks.
    let previewUrls = $state<string[]>([]);
    $effect(() => {
        const urls = files.map((file) => URL.createObjectURL(file));
        previewUrls = urls;

        return () => urls.forEach((url) => URL.revokeObjectURL(url));
    });

    function addFiles(fileList: FileList | null): void {
        if (!fileList) {
            return;
        }

        const room = Math.max(0, max - total);
        files = [...files, ...Array.from(fileList).slice(0, room)];
    }

    function handleChange(event: Event): void {
        const input = event.target as HTMLInputElement;
        addFiles(input.files);
        // Reset so selecting the same file again still fires `change`.
        input.value = '';
    }

    function removeExisting(image: MediaImage): void {
        removedIds = [...removedIds, image.id];
        existing = existing.filter((item) => item.id !== image.id);
    }

    function removeNew(index: number): void {
        files = files.filter((_, i) => i !== index);
    }
</script>

<div class="flex flex-col gap-2">
    {#if label}
        <span class="text-start text-[14px] text-[#7e7e7e]">{label}</span>
    {/if}

    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
        {#each existing as image (image.id)}
            <div
                class="group relative aspect-square overflow-hidden rounded-[12px] border border-black/10 bg-brand/10"
            >
                <img
                    src={image.url}
                    alt={image.name ?? ''}
                    class="h-full w-full object-cover"
                />
                <button
                    type="button"
                    aria-label={t('events.form.remove_image')}
                    onclick={() => removeExisting(image)}
                    class="absolute end-1.5 top-1.5 flex size-6 items-center justify-center rounded-full bg-black/55 text-white transition-colors hover:bg-[#f13e3e]"
                >
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Cancel01Icon}
                        class="size-3.5"
                    />
                </button>
            </div>
        {/each}

        {#each files as file, index (`${file.name}-${file.size}-${index}`)}
            <div
                class="group relative aspect-square overflow-hidden rounded-[12px] border border-black/10 bg-brand/10"
            >
                <img
                    src={previewUrls[index]}
                    alt={file.name}
                    class="h-full w-full object-cover"
                />
                <button
                    type="button"
                    aria-label={t('events.form.remove_image')}
                    onclick={() => removeNew(index)}
                    class="absolute end-1.5 top-1.5 flex size-6 items-center justify-center rounded-full bg-black/55 text-white transition-colors hover:bg-[#f13e3e]"
                >
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Cancel01Icon}
                        class="size-3.5"
                    />
                </button>
            </div>
        {/each}

        {#if canAddMore}
            <button
                type="button"
                onclick={() => inputEl?.click()}
                class="flex aspect-square flex-col items-center justify-center gap-1.5 rounded-[12px] border border-dashed border-black/25 bg-white text-[#7e7e7e] transition-colors hover:border-brand hover:text-brand"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={ImageAdd02Icon}
                    class="size-6"
                />
                <span class="px-1 text-center text-[11px] leading-tight">
                    {t('events.form.add_images')}
                </span>
            </button>
        {/if}
    </div>

    <input
        bind:this={inputEl}
        type="file"
        {accept}
        multiple
        onchange={handleChange}
        class="hidden"
    />

    {#if hint}
        <span class="text-start text-[12px] text-[#7e7e7e]">{hint}</span>
    {/if}

    <InputError message={error} />
</div>
