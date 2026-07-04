<script lang="ts">
    import {
        Cancel01Icon,
        FileUploadIcon,
        Link01Icon,
        Note01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';

    let {
        open = $bindable(false),
        taskTitle = 'تصميم واجهة لوحة التحكم',
        onsubmit,
    }: {
        open?: boolean;
        taskTitle?: string;
        onsubmit?: (payload: {
            file: File | null;
            url: string;
            notes: string;
        }) => void;
    } = $props();

    let url = $state('');
    let notes = $state('');
    let file = $state<File | null>(null);
    let fileName = $state('');

    function handleFileChange(event: Event) {
        const input = event.currentTarget as HTMLInputElement;
        const selected = input.files?.[0] ?? null;
        file = selected;
        fileName = selected?.name ?? '';
    }

    function handleSubmit() {
        onsubmit?.({ file, url, notes });
        open = false;
        url = '';
        notes = '';
        file = null;
        fileName = '';
    }

    function handleClose() {
        open = false;
    }
</script>

{#if open}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <button
            type="button"
            class="absolute inset-0 bg-black/40"
            aria-label="إغلاق"
            onclick={handleClose}
        ></button>

        <div
            class="th-card relative z-10 w-full max-w-md p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="complete-task-title"
        >
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <h2
                        id="complete-task-title"
                        class="text-lg font-semibold"
                        style="color: var(--th-text)"
                    >
                        تسليم المخرجات
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
                        {taskTitle}
                    </p>
                </div>
                <button
                    type="button"
                    class="flex size-8 items-center justify-center rounded-lg th-hover"
                    onclick={handleClose}
                    aria-label="إغلاق"
                >
                    <HugeiconsIcon
                        icon={Cancel01Icon}
                        size={18}
                        style="color: var(--th-text-muted)"
                    />
                </button>
            </div>

            <p class="mb-4 text-sm" style="color: var(--th-text-muted)">
                أرفق ملفاً أو رابطاً أو ملاحظات توضّح ما تم إنجازه. ستنتقل
                المهمة إلى <strong style="color: var(--th-review)"
                    >مراجعة</strong
                >.
            </p>

            <div class="space-y-3">
                <label
                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed p-4 transition-colors hover:border-[var(--th-primary)]"
                    style="border-color: var(--th-border)"
                >
                    <HugeiconsIcon
                        icon={FileUploadIcon}
                        size={20}
                        style="color: var(--th-primary)"
                    />
                    <div class="min-w-0 flex-1">
                        <span
                            class="text-sm font-medium"
                            style="color: var(--th-text)">رفع ملف</span
                        >
                        <p class="text-xs" style="color: var(--th-text-muted)">
                            {fileName || 'PDF، ZIP، صورة...'}
                        </p>
                    </div>
                    <input
                        type="file"
                        class="sr-only"
                        onchange={handleFileChange}
                    />
                </label>

                <div
                    class="flex items-center gap-3 rounded-xl border px-4 py-3"
                    style="border-color: var(--th-border)"
                >
                    <HugeiconsIcon
                        icon={Link01Icon}
                        size={20}
                        style="color: var(--th-primary)"
                    />
                    <input
                        type="url"
                        bind:value={url}
                        placeholder="رابط Figma، GitHub PR، Google Drive..."
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                        style="color: var(--th-text)"
                        dir="ltr"
                    />
                </div>

                <div
                    class="rounded-xl border px-4 py-3"
                    style="border-color: var(--th-border)"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <HugeiconsIcon
                            icon={Note01Icon}
                            size={18}
                            style="color: var(--th-primary)"
                        />
                        <span
                            class="text-sm font-medium"
                            style="color: var(--th-text)">ملاحظات</span
                        >
                    </div>
                    <textarea
                        bind:value={notes}
                        rows="3"
                        placeholder="وصف مختصر لما تم تسليمه..."
                        class="w-full resize-none bg-transparent text-sm outline-none"
                        style="color: var(--th-text)"
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button
                    type="button"
                    class="th-btn-primary flex-1 rounded-xl px-4 py-2.5 text-sm font-medium"
                    onclick={handleSubmit}
                >
                    إرسال للمراجعة
                </button>
                <button
                    type="button"
                    class="rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors th-hover"
                    style="border-color: var(--th-border); color: var(--th-text-muted)"
                    onclick={handleClose}
                >
                    إلغاء
                </button>
            </div>
        </div>
    </div>
{/if}
