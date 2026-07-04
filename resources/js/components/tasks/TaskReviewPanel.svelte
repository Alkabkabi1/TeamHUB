<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import { t } from '@/lib/i18n.svelte';

    type ReviewForm = {
        review_notes: string;
        processing: boolean;
        errors: Record<string, string | undefined>;
    };

    let {
        reviewForm,
        onApprove,
        onRequestChanges,
        onBlurEscape,
    }: {
        reviewForm: ReviewForm;
        onApprove: () => void;
        onRequestChanges: () => void;
        onBlurEscape: (event: KeyboardEvent) => void;
    } = $props();
</script>

<div
    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
>
    <h2 class="text-lg font-medium text-black dark:text-white">
        {t('tasks.review_panel')}
    </h2>

    <div class="mt-4 flex flex-col gap-2">
        <label
            for="review_notes"
            class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
            >{t('tasks.review_notes')}</label
        >
        <textarea
            id="review_notes"
            name="review_notes"
            bind:value={reviewForm.review_notes}
            rows="4"
            class="rounded-[10px] border border-black/15 bg-white px-4 py-3 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
            onkeydown={onBlurEscape}
        ></textarea>
        <InputError message={reviewForm.errors.review_notes} />
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <button
            type="button"
            onclick={onApprove}
            disabled={reviewForm.processing}
            class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:opacity-60"
        >
            {t('tasks.approve')}
        </button>
        <button
            type="button"
            onclick={onRequestChanges}
            disabled={reviewForm.processing}
            class="rounded-full bg-amber-100 px-5 py-2.5 text-sm font-medium text-amber-800 transition-colors hover:bg-amber-200 disabled:opacity-60"
        >
            {t('tasks.request_changes')}
        </button>
    </div>
</div>
