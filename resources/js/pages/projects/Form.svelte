<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import { t } from '@/lib/i18n.svelte';
    import {
        index as projectsIndex,
        store as projectStore,
        update as projectUpdate,
    } from '@/routes/projects';
    import type { ProjectSummary, SelectOption, WorkspaceRef } from '@/types';

    let {
        workspace,
        project = null,
        statusOptions = [],
        mode = 'create',
    }: {
        workspace: WorkspaceRef;
        project?: ProjectSummary | null;
        statusOptions?: SelectOption[];
        mode?: 'create' | 'edit';
    } = $props();

    const isEdit = $derived(mode === 'edit');

    const form = useForm({
        name: project?.name ?? '',
        description: project?.description ?? '',
        status: project?.status ?? 'active',
        image: null as File | null,
        remove_image: false as boolean,
    });

    let preview = $state<string | null>(project?.image_url ?? null);

    function onImageChange(e: Event): void {
        const input = e.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        form.image = file;
        form.remove_image = false;
        preview = file
            ? URL.createObjectURL(file)
            : (project?.image_url ?? null);
    }

    function removeImage(): void {
        form.image = null;
        form.remove_image = true;
        preview = null;
    }

    function submit(e: SubmitEvent): void {
        e.preventDefault();

        if (isEdit && project) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(
                projectUpdate([workspace.id, project.id]).url,
                { forceFormData: true, preserveScroll: true },
            );

            return;
        }

        form.post(projectStore(workspace.id).url, {
            forceFormData: true,
            preserveScroll: true,
        });
    }
</script>

<AppHead
    title={isEdit
        ? t('project.form.edit_title')
        : t('project.form.create_title')}
/>

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        {#if isEdit && project}
            <ProjectManageShell
                active="settings"
                {workspace}
                project={{ id: project.id, name: project.name }}
            />
        {/if}

        <div class="flex items-center justify-between gap-4">
            <h1 class="text-start text-xl text-[#5f5f5f] sm:text-2xl">
                {isEdit
                    ? t('project.form.edit_title')
                    : t('project.form.create_title')}
            </h1>
            <span class="text-start text-sm text-[#7e7e7e]"
                >{workspace.name}</span
            >
        </div>

        <form
            onsubmit={submit}
            class="flex flex-col gap-6 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:p-8"
        >
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label
                    for="project-name"
                    class="text-start text-[14px] text-[#7e7e7e]"
                >
                    {t('project.form.name')}
                    <span class="text-[#f13e3e]">*</span>
                </label>
                <input
                    id="project-name"
                    name="name"
                    type="text"
                    required
                    bind:value={form.name}
                    class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                />
                <InputError message={form.errors.name} />
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
                <label
                    for="project-description"
                    class="text-start text-[14px] text-[#7e7e7e]"
                >
                    {t('project.form.description')}
                </label>
                <textarea
                    id="project-description"
                    name="description"
                    rows="4"
                    bind:value={form.description}
                    class="rounded-[10px] border border-black/20 bg-white p-4 text-start text-[13px] text-black outline-none placeholder:text-black/30 focus:border-brand"
                ></textarea>
                <InputError message={form.errors.description} />
            </div>

            <!-- Status -->
            <div class="flex flex-col gap-2">
                <label class="text-start text-[14px] text-[#7e7e7e]">
                    {t('project.form.status')}
                </label>
                <FilterSelect
                    name="status"
                    ariaLabel={t('project.form.status')}
                    bind:value={form.status}
                    options={statusOptions}
                />
                <InputError message={form.errors.status} />
            </div>

            <!-- Image -->
            <div class="flex flex-col gap-2">
                <label
                    for="project-image"
                    class="text-start text-[14px] text-[#7e7e7e]"
                >
                    {t('project.form.image')}
                </label>
                {#if preview}
                    <div class="flex items-center gap-4">
                        <img
                            src={preview}
                            alt=""
                            class="size-20 rounded-[12px] object-cover"
                        />
                        <button
                            type="button"
                            onclick={removeImage}
                            class="rounded-full bg-[#f13e3e]/10 px-4 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                        >
                            {t('project.form.remove_image')}
                        </button>
                    </div>
                {/if}
                <input
                    id="project-image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onchange={onImageChange}
                    class="text-start text-[13px] text-[#5f5f5f] file:mr-4 file:rounded-full file:border-0 file:bg-brand/15 file:px-4 file:py-2 file:text-[12px] file:text-brand"
                />
                <p class="text-start text-[12px] text-[#7e7e7e]">
                    {t('project.form.image_hint')}
                </p>
                <InputError message={form.errors.image} />
            </div>

            <div class="flex flex-wrap items-center justify-start gap-3">
                <button
                    type="submit"
                    disabled={form.processing}
                    class="cursor-pointer rounded-full bg-brand px-8 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {isEdit
                        ? t('project.form.submit_update')
                        : t('project.form.submit_create')}
                </button>
                <button
                    type="button"
                    onclick={() =>
                        router.visit(projectsIndex(workspace.id).url)}
                    class="cursor-pointer rounded-full bg-brand/20 px-8 py-2.5 text-[13px] text-brand transition-colors hover:bg-brand/30"
                >
                    {t('project.form.cancel')}
                </button>
            </div>
        </form>
    </div>
</div>
