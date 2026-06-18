<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import BackLink from '@/components/BackLink.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import ImageUploader from '@/components/ImageUploader.svelte';
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubRef, MediaImage } from '@/types';

    type EventData = {
        id: number;
        title: string;
        description: string | null;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        capacity: number | null;
        status: string;
        images: MediaImage[];
    };

    let {
        club,
        committee = null,
        event = null,
        mode = 'create',
    }: {
        club: ClubRef;
        committee?: ClubRef | null;
        event?: EventData | null;
        mode?: 'create' | 'edit';
    } = $props();

    const isEdit = $derived(mode === 'edit');

    // Events may belong to a club or to a committee within it; the committee
    // context (when present) drives the submit, cancel and back URLs.
    const basePath = $derived(
        committee
            ? `/clubs/${club.id}/committees/${committee.id}`
            : `/clubs/${club.id}`,
    );
    const managePath = $derived(`${basePath}/manage`);
    const contextName = $derived(committee?.name ?? club.name);

    // Normalize datetime-local input values from ISO strings.
    // datetime-local expects "YYYY-MM-DDTHH:MM".
    function toDatetimeLocal(iso: string | null | undefined): string {
        return iso ? iso.slice(0, 16) : '';
    }

    const form = useForm({
        title: event?.title ?? '',
        description: event?.description ?? '',
        starts_at: toDatetimeLocal(event?.starts_at),
        ends_at: toDatetimeLocal(event?.ends_at),
        location: event?.location ?? '',
        capacity: event?.capacity ?? (null as number | null),
        status: event?.status ?? 'active',
        images: [] as File[],
        removed_media: [] as number[],
    });

    let existingImages = $state<MediaImage[]>(event?.images ?? []);

    const statusOptions = $derived([
        { value: 'active', label: t('events.status_labels.active') },
        { value: 'draft', label: t('events.status_labels.draft') },
        { value: 'cancelled', label: t('events.status_labels.cancelled') },
    ]);

    function submit(e: SubmitEvent): void {
        e.preventDefault();

        if (isEdit && event) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(
                `${basePath}/events/${event.id}`,
                { forceFormData: true, preserveScroll: true },
            );

            return;
        }

        form.post(`${basePath}/events`, {
            forceFormData: true,
            preserveScroll: true,
        });
    }
</script>

<AppHead
    title={isEdit ? t('events.form.edit_title') : t('events.form.create_title')}
/>

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <div class="flex flex-col gap-3">
            <BackLink href={managePath} label={t('app.back_to_manage')} />
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-start text-xl text-[#5f5f5f] sm:text-2xl">
                    {isEdit
                        ? t('events.form.edit_title')
                        : t('events.form.create_title')}
                </h1>
                <span class="text-start text-sm text-[#7e7e7e]"
                    >{contextName}</span
                >
            </div>
        </div>

        <form
            onsubmit={submit}
            class="flex flex-col gap-6 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:p-8"
        >
            <div class="flex flex-col gap-5">
                <!-- Title -->
                <div class="flex flex-col gap-2">
                    <label
                        for="event-title"
                        class="text-start text-[14px] text-[#7e7e7e]"
                    >
                        {t('events.form.field_title')}
                        <span class="text-[#f13e3e]">*</span>
                    </label>
                    <input
                        id="event-title"
                        name="title"
                        type="text"
                        required
                        bind:value={form.title}
                        class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                    />
                    <InputError message={form.errors.title} />
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-2">
                    <label
                        for="event-description"
                        class="text-start text-[14px] text-[#7e7e7e]"
                    >
                        {t('events.form.field_description')}
                    </label>
                    <textarea
                        id="event-description"
                        name="description"
                        rows="3"
                        bind:value={form.description}
                        class="rounded-[10px] border border-black/20 bg-white p-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                    ></textarea>
                    <InputError message={form.errors.description} />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <!-- Starts At -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="event-starts-at"
                            class="text-start text-[14px] text-[#7e7e7e]"
                        >
                            {t('events.form.field_starts_at')}
                            <span class="text-[#f13e3e]">*</span>
                        </label>
                        <input
                            id="event-starts-at"
                            name="starts_at"
                            type="datetime-local"
                            required
                            bind:value={form.starts_at}
                            class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none focus:border-brand"
                        />
                        <InputError message={form.errors.starts_at} />
                    </div>

                    <!-- Ends At -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="event-ends-at"
                            class="text-start text-[14px] text-[#7e7e7e]"
                        >
                            {t('events.form.field_ends_at')}
                            <span class="text-[#f13e3e]">*</span>
                        </label>
                        <input
                            id="event-ends-at"
                            name="ends_at"
                            type="datetime-local"
                            required
                            bind:value={form.ends_at}
                            class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none focus:border-brand"
                        />
                        <InputError message={form.errors.ends_at} />
                    </div>

                    <!-- Location -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="event-location"
                            class="text-start text-[14px] text-[#7e7e7e]"
                        >
                            {t('events.form.field_location')}
                        </label>
                        <input
                            id="event-location"
                            name="location"
                            type="text"
                            bind:value={form.location}
                            class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                        />
                        <InputError message={form.errors.location} />
                    </div>

                    <!-- Capacity -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="event-capacity"
                            class="text-start text-[14px] text-[#7e7e7e]"
                        >
                            {t('events.form.field_capacity')}
                        </label>
                        <input
                            id="event-capacity"
                            name="capacity"
                            type="number"
                            min="1"
                            bind:value={form.capacity}
                            class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                        />
                        <InputError message={form.errors.capacity} />
                    </div>
                </div>

                <!-- Status -->
                <div class="flex flex-col gap-2">
                    <label
                        for="event-status"
                        class="text-start text-[14px] text-[#7e7e7e]"
                    >
                        {t('events.form.field_status')}
                    </label>
                    <FilterSelect
                        id="event-status"
                        class="min-h-10 h-10 rounded-[10px] border-black/20 px-5 text-[13px] text-black"
                        name="status"
                        ariaLabel={t('events.form.field_status')}
                        bind:value={form.status}
                        options={statusOptions}
                    />
                    <InputError message={form.errors.status} />
                </div>

                <!-- Images -->
                <ImageUploader
                    label={t('events.form.field_images')}
                    hint={t('events.form.images_hint')}
                    bind:files={form.images}
                    bind:existing={existingImages}
                    bind:removedIds={form.removed_media}
                    error={form.errors.images}
                />
            </div>

            <div class="flex flex-wrap items-center justify-start gap-3">
                <button
                    type="submit"
                    disabled={form.processing}
                    class="cursor-pointer rounded-full bg-brand px-8 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {isEdit
                        ? t('events.form.submit_update')
                        : t('events.form.submit_create')}
                </button>
                <button
                    type="button"
                    onclick={() => router.visit(managePath)}
                    class="cursor-pointer rounded-full bg-brand/20 px-8 py-2.5 text-[13px] text-brand transition-colors hover:bg-brand/30"
                >
                    {t('events.form.cancel')}
                </button>
            </div>
        </form>
    </div>
</div>
