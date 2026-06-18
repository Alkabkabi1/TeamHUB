<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import BackLink from '@/components/BackLink.svelte';
    import ImageUploader from '@/components/ImageUploader.svelte';
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { ClubRef, MediaImage } from '@/types';

    type NewsPost = {
        id: number;
        title: string;
        body: string;
        images: MediaImage[];
    };

    let {
        club,
        committee = null,
        post = null,
        mode = 'create',
    }: {
        club: ClubRef;
        committee?: ClubRef | null;
        post?: NewsPost | null;
        mode?: 'create' | 'edit';
    } = $props();

    const isEdit = $derived(mode === 'edit');

    // News may belong to a club or to a committee within it; the committee
    // context (when present) drives the submit, cancel and back URLs.
    const basePath = $derived(
        committee
            ? `/clubs/${club.id}/committees/${committee.id}`
            : `/clubs/${club.id}`,
    );
    const managePath = $derived(`${basePath}/manage`);
    const contextName = $derived(committee?.name ?? club.name);

    const form = useForm({
        title: post?.title ?? '',
        body: post?.body ?? '',
        images: [] as File[],
        removed_media: [] as number[],
    });

    let existingImages = $state<MediaImage[]>(post?.images ?? []);

    function submit(e: SubmitEvent): void {
        e.preventDefault();

        if (isEdit && post) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(
                `${basePath}/news/${post.id}`,
                {
                    forceFormData: true,
                    preserveScroll: true,
                },
            );

            return;
        }

        form.post(`${basePath}/news`, {
            forceFormData: true,
            preserveScroll: true,
        });
    }
</script>

<AppHead
    title={isEdit ? t('news.form.edit_title') : t('news.form.create_title')}
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
                        ? t('news.form.edit_title')
                        : t('news.form.create_title')}
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
            <!-- Title -->
            <div class="flex flex-col gap-2">
                <label
                    for="news-title"
                    class="text-start text-[14px] text-[#7e7e7e]"
                >
                    {t('news.form.title')}
                    <span class="text-[#f13e3e]">*</span>
                </label>
                <input
                    id="news-title"
                    name="title"
                    type="text"
                    required
                    bind:value={form.title}
                    class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                />
                <InputError message={form.errors.title} />
            </div>

            <!-- Body -->
            <div class="flex flex-col gap-2">
                <label
                    for="news-body"
                    class="text-start text-[14px] text-[#7e7e7e]"
                >
                    {t('news.form.body')}
                    <span class="text-[#f13e3e]">*</span>
                </label>
                <textarea
                    id="news-body"
                    name="body"
                    rows="6"
                    required
                    bind:value={form.body}
                    class="rounded-[10px] border border-black/20 bg-white p-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                ></textarea>
                <InputError message={form.errors.body} />
            </div>

            <!-- Images -->
            <ImageUploader
                label={t('news.form.image')}
                hint={t('news.form.images_hint')}
                bind:files={form.images}
                bind:existing={existingImages}
                bind:removedIds={form.removed_media}
                error={form.errors.images}
            />

            <div class="flex flex-wrap items-center justify-start gap-3">
                <button
                    type="submit"
                    disabled={form.processing}
                    class="cursor-pointer rounded-full bg-brand px-8 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {isEdit
                        ? t('news.form.submit_update')
                        : t('news.form.submit')}
                </button>
                <button
                    type="button"
                    onclick={() => router.visit(managePath)}
                    class="cursor-pointer rounded-full bg-brand/20 px-8 py-2.5 text-[13px] text-brand transition-colors hover:bg-brand/30"
                >
                    {t('news.form.cancel')}
                </button>
            </div>
        </form>
    </div>
</div>
