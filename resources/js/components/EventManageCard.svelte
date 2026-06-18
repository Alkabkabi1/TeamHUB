<script lang="ts">
    import {
        Calendar02Icon,
        QrCodeIcon,
        UserGroupIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';

    /**
     * Managed-event card for the supervisor dashboard (Figma node 52:944):
     * status badge + title, optional date row, registrations row, and the
     * edit / delete / scan-attendance actions (each rendered only when its
     * props are supplied, so a scanner-only role sees just the scan button).
     */
    let {
        title,
        statusLabel,
        dateLabel = null,
        registrationsLabel,
        editHref = null,
        editLabel = '',
        deleteLabel = null,
        onDelete,
        scanHref = null,
        scanLabel = '',
    }: {
        title: string;
        statusLabel: string;
        dateLabel?: string | null;
        registrationsLabel: string;
        editHref?: string | null;
        editLabel?: string;
        deleteLabel?: string | null;
        onDelete?: () => void;
        scanHref?: string | null;
        scanLabel?: string;
    } = $props();
</script>

<DashboardCard
    class="flex h-full flex-col gap-3 transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
>
    <div class="flex items-start justify-between gap-3">
        <p class="text-start text-[14px] text-black">{title}</p>
        <StatusBadge label={statusLabel} />
    </div>
    <div
        class="mt-2 flex flex-col items-start gap-2 text-[12px] text-[#7e7e7e]"
    >
        {#if dateLabel}
            <span class="flex items-center gap-1.5">
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Calendar02Icon}
                    class="size-4"
                />
                <span>{dateLabel}</span>
            </span>
        {/if}
        <span class="flex items-center gap-1.5">
            <HugeiconsIcon
                strokeWidth={2}
                icon={UserGroupIcon}
                class="size-4"
            />
            <span>{registrationsLabel}</span>
        </span>
    </div>
    <div class="mt-auto flex flex-wrap items-center justify-start gap-2.5">
        {#if scanHref}
            <Link
                href={scanHref}
                class="flex cursor-pointer items-center gap-2 rounded-full bg-brand px-5 py-2 text-[12px] text-white transition-colors hover:bg-brand-dark"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={QrCodeIcon}
                    class="size-4"
                />
                {scanLabel}
            </Link>
        {/if}
        {#if editHref}
            <Link
                href={editHref}
                class="cursor-pointer rounded-full bg-brand/25 px-5 py-2 text-[12px] text-brand transition-colors hover:bg-brand/40"
            >
                {editLabel}
            </Link>
        {/if}
        {#if deleteLabel}
            <button
                type="button"
                onclick={onDelete}
                class="cursor-pointer rounded-full bg-[#f13e3e]/10 px-5 py-2 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
            >
                {deleteLabel}
            </button>
        {/if}
    </div>
</DashboardCard>
