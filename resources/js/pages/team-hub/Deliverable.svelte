<script lang="ts">
    export const layout = () => null;

    import {
        CheckmarkCircle01Icon,
        File01Icon,
        Link01Icon,
        Upload04Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import CompleteTaskModal from '@/components/team-hub/CompleteTaskModal.svelte';
    import PriorityDot from '@/components/team-hub/PriorityDot.svelte';
    import TaskStatusBadge from '@/components/team-hub/TaskStatusBadge.svelte';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';

    let modalOpen = $state(false);
    let submitted = $state<{
        file: string | null;
        url: string;
        notes: string;
    } | null>(null);

    const demoTask = {
        title: 'تصميم واجهة لوحة التحكم',
        project: 'تطوير منصة الفريق',
        assignee: 'أحمد',
        due: '10 يونيو 2026',
    };

    function handleSubmit(payload: {
        file: File | null;
        url: string;
        notes: string;
    }) {
        submitted = {
            file: payload.file?.name ?? null,
            url: payload.url,
            notes: payload.notes,
        };
    }
</script>

<TeamHubLayout
    title="تسليم المخرجات — Team Hub"
    activePath="/preview/team-hub/deliverable"
>
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
        <header class="mb-6">
            <h1 class="text-xl font-bold" style="color: var(--th-text)">
                تسليم المخرجات
            </h1>
            <p
                class="mt-1 max-w-2xl text-sm leading-relaxed"
                style="color: var(--th-text-muted)"
            >
                عند إكمال المهمة، يرفع المسؤول ملفاً أو رابطاً أو ملاحظات. تنتقل
                المهمة إلى
                <strong style="color: var(--th-review)">مراجعة</strong> حتى يوافق
                قائد المشروع.
            </p>
        </header>

        <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
            <article class="th-card p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold" style="color: var(--th-text)">
                            {demoTask.title}
                        </h2>
                        <p
                            class="mt-1 text-sm"
                            style="color: var(--th-text-muted)"
                        >
                            {demoTask.project}
                        </p>
                    </div>
                    <TaskStatusBadge status="in_progress" />
                </div>

                <dl class="mb-5 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt style="color: var(--th-text-muted)">المسؤول</dt>
                        <dd style="color: var(--th-text)">
                            {demoTask.assignee}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color: var(--th-text-muted)">الموعد</dt>
                        <dd style="color: var(--th-text)">{demoTask.due}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color: var(--th-text-muted)">الأولوية</dt>
                        <dd><PriorityDot priority="high" /></dd>
                    </div>
                </dl>

                <button
                    type="button"
                    class="th-btn-primary flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium"
                    onclick={() => (modalOpen = true)}
                >
                    <HugeiconsIcon icon={Upload04Icon} size={18} color="#fff" />
                    إرسال للمراجعة
                </button>
            </article>

            <div class="space-y-4">
                <div class="th-card p-5">
                    <h3
                        class="mb-3 text-sm font-semibold"
                        style="color: var(--th-text)"
                    >
                        سير العمل
                    </h3>
                    <ol class="space-y-3 text-sm">
                        {#each [{ label: 'قيد الانتظار', active: false }, { label: 'قيد التنفيذ', active: !submitted }, { label: 'مراجعة', active: !!submitted }, { label: 'مكتمل', active: false }] as step, i (step.label)}
                            <li class="flex items-center gap-3">
                                <span
                                    class="flex size-7 items-center justify-center rounded-full text-xs font-bold"
                                    style="background: {step.active
                                        ? 'var(--th-primary)'
                                        : 'color-mix(in srgb, var(--th-border) 80%, transparent)'};
                                        color: {step.active
                                        ? '#fff'
                                        : 'var(--th-text-muted)'}"
                                >
                                    {i + 1}
                                </span>
                                <span
                                    class="font-medium"
                                    style="color: {step.active
                                        ? 'var(--th-text)'
                                        : 'var(--th-text-muted)'}"
                                >
                                    {step.label}
                                </span>
                            </li>
                        {/each}
                    </ol>
                </div>

                {#if submitted}
                    <div class="th-card p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <HugeiconsIcon
                                icon={CheckmarkCircle01Icon}
                                size={20}
                                style="color: var(--th-success)"
                            />
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--th-text)"
                            >
                                تم الإرسال — في المراجعة
                            </h3>
                        </div>
                        <TaskStatusBadge status="review" />

                        <ul class="mt-4 space-y-2 text-sm">
                            {#if submitted.file}
                                <li
                                    class="flex items-center gap-2"
                                    style="color: var(--th-text)"
                                >
                                    <HugeiconsIcon
                                        icon={File01Icon}
                                        size={16}
                                        style="color: var(--th-primary)"
                                    />
                                    {submitted.file}
                                </li>
                            {/if}
                            {#if submitted.url}
                                <li
                                    class="flex items-center gap-2"
                                    style="color: var(--th-text)"
                                >
                                    <HugeiconsIcon
                                        icon={Link01Icon}
                                        size={16}
                                        style="color: var(--th-primary)"
                                    />
                                    <span class="truncate" dir="ltr"
                                        >{submitted.url}</span
                                    >
                                </li>
                            {/if}
                            {#if submitted.notes}
                                <li
                                    class="mt-2 rounded-lg p-3 text-sm"
                                    style="background: var(--th-bg); color: var(--th-text-muted)"
                                >
                                    {submitted.notes}
                                </li>
                            {/if}
                        </ul>

                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                class="th-btn-primary flex-1 rounded-xl px-3 py-2 text-xs font-medium"
                            >
                                موافقة (قائد المشروع)
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-xl border px-3 py-2 text-xs font-medium"
                                style="border-color: var(--th-border); color: var(--th-text-muted)"
                            >
                                طلب تعديلات
                            </button>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</TeamHubLayout>

<CompleteTaskModal
    bind:open={modalOpen}
    taskTitle={demoTask.title}
    onsubmit={handleSubmit}
/>
