<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import InputError from '@/components/InputError.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';
    import type { ClubRef } from '@/types';

    type CommitteeRef = {
        id: number;
        name: string;
        logo_url: string | null;
        status: string;
    };

    type ProjectFile = {
        id: number;
        title: string;
        description: string | null;
        type: string;
        format: string;
        access: string;
        published_at: string | null;
        download_url: string | null;
    };

    let {
        club,
        committee,
        files = [],
        canManageFiles = false,
    }: {
        club: ClubRef & { logo_url?: string | null };
        committee: CommitteeRef;
        files?: ProjectFile[];
        canManageFiles?: boolean;
    } = $props();

    const form = useForm({
        title: '',
        description: '',
        type: 'download',
        access: 'عام',
        file: null as File | null,
    });

    let selectedFile = $state('');

    function onFileChange(event: Event): void {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        form.file = file;
        selectedFile = file?.name ?? '';
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(`/clubs/${club.id}/committees/${committee.id}/files`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.type = 'download';
                form.access = 'عام';
                selectedFile = '';
            },
        });
    }

    function removeFile(id: number): void {
        router.delete(
            `/clubs/${club.id}/committees/${committee.id}/files/${id}`,
            {
                preserveScroll: true,
            },
        );
    }
</script>

<AppHead title={`${committee.name} — ${t('app.files')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <ProjectManageShell active="files" {club} {committee} />

        {#if canManageFiles}
            <form
                onsubmit={submit}
                class="grid gap-4 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] lg:grid-cols-2"
            >
                <div class="space-y-1 lg:col-span-2 text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('app.files')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">
                        {t('resources.hero_subtitle')}
                    </p>
                </div>

                <div class="flex flex-col gap-2 lg:col-span-2">
                    <label for="title" class="text-sm text-[#5f5f5f]"
                        >{t('news.form.title')}</label
                    >
                    <input
                        id="title"
                        name="title"
                        bind:value={form.title}
                        class="h-11 rounded-[10px] border border-black/15 px-4 text-sm outline-none focus:border-brand"
                    />
                    <InputError message={form.errors.title} />
                </div>

                <div class="flex flex-col gap-2 lg:col-span-2">
                    <label for="description" class="text-sm text-[#5f5f5f]"
                        >{t('tasks.description')}</label
                    >
                    <textarea
                        id="description"
                        name="description"
                        bind:value={form.description}
                        rows="3"
                        class="rounded-[10px] border border-black/15 px-4 py-3 text-sm outline-none focus:border-brand"
                    ></textarea>
                    <InputError message={form.errors.description} />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="type" class="text-sm text-[#5f5f5f]"
                        >{t('resources.file_format')}</label
                    >
                    <select
                        id="type"
                        name="type"
                        bind:value={form.type}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand"
                    >
                        <option value="download"
                            >{t('resources.download')}</option
                        >
                        <option value="media"
                            >{t('resources.media_gallery')}</option
                        >
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="access" class="text-sm text-[#5f5f5f]"
                        >{t('resources.access')}</label
                    >
                    <select
                        id="access"
                        name="access"
                        bind:value={form.access}
                        class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand"
                    >
                        <option value="عام">{t('app.public')}</option>
                        <option value="خاص">{t('app.private')}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 lg:col-span-2">
                    <label for="file" class="text-sm text-[#5f5f5f]"
                        >{t('news.form.image')}</label
                    >
                    <input
                        id="file"
                        name="file"
                        type="file"
                        onchange={onFileChange}
                        class="text-sm text-[#5f5f5f] file:me-3 file:rounded-full file:border-0 file:bg-brand/10 file:px-3 file:py-2 file:text-[12px] file:text-brand"
                    />
                    {#if selectedFile}
                        <p class="text-xs text-[#7e7e7e]">{selectedFile}</p>
                    {/if}
                    <InputError message={form.errors.file} />
                </div>

                <div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        class="rounded-full bg-brand px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
                    >
                        {t('app.save')}
                    </button>
                </div>
            </form>
        {/if}

        <DashboardCard class="flex flex-col gap-4">
            <div class="space-y-1 text-start">
                <h2 class="text-lg font-medium text-black">{t('app.files')}</h2>
                <p class="text-sm text-[#7e7e7e]">{committee.name}</p>
            </div>

            {#if files.length === 0}
                <EmptyState
                    title={t('resources.no_resources')}
                    description=""
                />
            {:else}
                <div class="space-y-3">
                    {#each files as file (file.id)}
                        <div class="rounded-[14px] border border-black/10 p-4">
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                            >
                                <div class="space-y-1 text-start">
                                    <p class="text-sm font-medium text-black">
                                        {file.title}
                                    </p>
                                    <p class="text-xs text-[#7e7e7e]">
                                        {file.description}
                                    </p>
                                    <div
                                        class="flex flex-wrap gap-2 text-xs text-[#9a9a9a]"
                                    >
                                        <span>{file.format}</span>
                                        <span>•</span>
                                        <span>{file.access}</span>
                                        {#if file.published_at}
                                            <span>•</span>
                                            <span
                                                >{formatDate(
                                                    file.published_at,
                                                )}</span
                                            >
                                        {/if}
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    {#if file.download_url}
                                        <a
                                            href={file.download_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            class="rounded-full bg-brand/10 px-4 py-2 text-xs font-medium text-brand transition-colors hover:bg-brand/20"
                                        >
                                            {t('resources.download')}
                                        </a>
                                    {/if}
                                    {#if canManageFiles}
                                        <button
                                            type="button"
                                            onclick={() => removeFile(file.id)}
                                            class="rounded-full bg-rose-50 px-4 py-2 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100"
                                        >
                                            {t('committees.dashboard.delete')}
                                        </button>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </DashboardCard>
    </div>
</div>
