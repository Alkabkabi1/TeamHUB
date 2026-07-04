<script lang="ts">
    export const layout = () => null;

    import {
        ArrowLeft01Icon,
        DashboardSquare01Icon,
        Folder01Icon,
        TaskDone01Icon,
        Upload04Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';

    const pages = [
        {
            href: '/preview/team-hub/dashboard',
            title: 'لوحة التحكم',
            description: 'KPIs، المشاريع، مهام اليوم، التقويم، والنشاط',
            icon: DashboardSquare01Icon,
        },
        {
            href: '/preview/team-hub/tasks',
            title: 'المهام',
            description: 'جدول المهام مع الحالة والأولوية والمسؤول',
            icon: TaskDone01Icon,
        },
        {
            href: '/preview/team-hub/projects',
            title: 'المشاريع',
            description: 'بطاقات المشاريع مع نسبة الإنجاز والفريق',
            icon: Folder01Icon,
        },
        {
            href: '/preview/team-hub/deliverable',
            title: 'تسليم المخرجات',
            description: 'نافذة رفع ملف / رابط / ملاحظات عند إكمال المهمة',
            icon: Upload04Icon,
        },
    ];
</script>

<TeamHubLayout title="Team Hub — معاينة التصميم" activePath="/preview/team-hub">
    <div class="flex min-h-screen flex-col p-6 lg:p-8">
        <header class="mb-8">
            <p class="text-sm font-medium" style="color: var(--th-primary)">
                معاينة التصميم
            </p>
            <h1 class="mt-1 text-2xl font-bold" style="color: var(--th-text)">
                صفحات Team Hub
            </h1>
            <p
                class="mt-2 max-w-2xl text-sm"
                style="color: var(--th-text-muted)"
            >
                صفحات منفصلة لاختبار نظام التصميم — الوضع الفاتح والداكن، RTL،
                والمكونات المشتركة. استخدم مفتاح الوضع في الشريط الجانبي للتبديل
                بين الثيمين.
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2">
            {#each pages as page (page.href)}
                <Link
                    href={page.href}
                    class="th-card group flex flex-col p-5 transition-shadow hover:shadow-md"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div
                            class="flex size-11 items-center justify-center rounded-xl transition-colors"
                            style="background: color-mix(in srgb, var(--th-primary) 12%, transparent)"
                        >
                            <HugeiconsIcon
                                icon={page.icon}
                                size={22}
                                style="color: var(--th-primary)"
                            />
                        </div>
                        <HugeiconsIcon
                            icon={ArrowLeft01Icon}
                            size={18}
                            class="opacity-0 transition-opacity group-hover:opacity-100"
                            style="color: var(--th-text-muted)"
                        />
                    </div>
                    <h2 class="font-semibold" style="color: var(--th-text)">
                        {page.title}
                    </h2>
                    <p
                        class="mt-1 text-sm leading-relaxed"
                        style="color: var(--th-text-muted)"
                    >
                        {page.description}
                    </p>
                </Link>
            {/each}
        </div>
    </div>
</TeamHubLayout>
