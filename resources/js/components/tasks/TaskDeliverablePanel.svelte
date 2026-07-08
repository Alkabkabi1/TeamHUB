<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';

    type DeliverableTask = {
        status: 'todo' | 'in_progress' | 'review' | 'done';
        deliverable_url: string | null;
        deliverable_notes: string | null;
        deliverable_file: { name: string; url: string } | null;
        has_deliverable: boolean;
    };

    type DeliverableForm = {
        deliverable_file: File | null;
        deliverable_url: string;
        deliverable_notes: string;
        processing: boolean;
        errors: Record<string, string | undefined>;
    };

    let {
        task,
        canSubmitDeliverable = false,
        deliverableForm,
        selectedFileName = '',
        onDeliverableFileChange,
        onSubmit,
        onBlurEscape,
    }: {
        task: DeliverableTask;
        canSubmitDeliverable?: boolean;
        deliverableForm: DeliverableForm;
        selectedFileName?: string;
        onDeliverableFileChange: (event: Event) => void;
        onSubmit: (event: SubmitEvent) => void;
        onBlurEscape: (event: KeyboardEvent) => void;
    } = $props();
</script>

<div
    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
>
    <h2 class="text-lg font-medium text-black dark:text-white">
        {t('tasks.deliverable_section')}
    </h2>
    <p class="mt-1 text-sm text-[#7e7e7e] dark:text-[#94a3b8]">
        {t('tasks.deliverable_help')}
    </p>

    {#if task.has_deliverable}
        <div
            class="mt-4 space-y-3 rounded-2xl border border-black/10 bg-slate-50 p-4 text-sm dark:border-white/10 dark:bg-white/5 dark:text-[#cbd5e1]"
        >
            {#if task.deliverable_file}
                <p>
                    <span class="font-medium text-black dark:text-white"
                        >{t('tasks.deliverable_file')}:</span
                    >
                    <a
                        href={task.deliverable_file.url}
                        class="text-brand underline"
                        target="_blank"
                        rel="noreferrer"
                    >
                        {task.deliverable_file.name}
                    </a>
                </p>
            {/if}
            {#if task.deliverable_url}
                <p>
                    <span class="font-medium text-black dark:text-white"
                        >{t('tasks.deliverable_link')}:</span
                    >
                    <a
                        href={task.deliverable_url}
                        class="text-brand underline"
                        target="_blank"
                        rel="noreferrer"
                    >
                        {task.deliverable_url}
                    </a>
                </p>
            {/if}
            {#if task.deliverable_notes}
                <p
                    class="whitespace-pre-wrap text-[#5f5f5f] dark:text-[#cbd5e1]"
                >
                    {task.deliverable_notes}
                </p>
            {/if}
        </div>
    {/if}

    {#if canSubmitDeliverable && task.status !== 'done'}
        <form onsubmit={onSubmit} class="mt-4 space-y-4">
            <div class="flex flex-col gap-2">
                <label
                    for="deliverable_file"
                    class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >{t('tasks.deliverable_file')}</label
                >
                <input
                    id="deliverable_file"
                    name="deliverable_file"
                    type="file"
                    onchange={onDeliverableFileChange}
                    class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1] file:me-4 file:rounded-full file:border-0 file:bg-brand/15 file:px-4 file:py-2 file:text-xs file:text-brand"
                />
                {#if selectedFileName}
                    <p class="text-xs text-[#7e7e7e] dark:text-[#94a3b8]">
                        {selectedFileName}
                    </p>
                {/if}
                <InputError message={deliverableForm.errors.deliverable_file} />
            </div>

            <div class="flex flex-col gap-2">
                <label
                    for="deliverable_url"
                    class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >{t('tasks.deliverable_link')}</label
                >
                <input
                    id="deliverable_url"
                    name="deliverable_url"
                    type="url"
                    bind:value={deliverableForm.deliverable_url}
                    dir="ltr"
                    class="h-11 rounded-[10px] border border-black/15 bg-white px-4 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                />
                <InputError message={deliverableForm.errors.deliverable_url} />
            </div>

            <div class="flex flex-col gap-2">
                <label
                    for="deliverable_notes"
                    class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >{t('tasks.deliverable_notes')}</label
                >
                <textarea
                    id="deliverable_notes"
                    name="deliverable_notes"
                    bind:value={deliverableForm.deliverable_notes}
                    rows="4"
                    class="rounded-[10px] border border-black/15 bg-white px-4 py-3 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                    onkeydown={onBlurEscape}
                ></textarea>
                <InputError
                    message={deliverableForm.errors.deliverable_notes}
                />
            </div>

            <button
                type="submit"
                disabled={deliverableForm.processing}
                class="rounded-full bg-brand px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
            >
                {t('tasks.submit_deliverable')}
            </button>
        </form>
    {/if}
</div>
