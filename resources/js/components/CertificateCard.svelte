<script lang="ts">
    /**
     * Certificate preview card (placeholder image, title, club + issue date,
     * certificate number, download link). Used on the student dashboard and
     * mirrors the EventCard/NewsCard card conventions.
     */
    import {
        Certificate01Icon,
        FileDownloadIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        title: string;
        clubName: string;
        issuedAt: string;
        certificateNo: string;
        downloadHref: string;
    };

    let { title, clubName, issuedAt, certificateNo, downloadHref }: Props =
        $props();
</script>

<article
    class="flex min-h-[280px] flex-col gap-3 rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
>
    <div
        class="flex h-[140px] shrink-0 items-center justify-center rounded-[20px] bg-brand/15"
    >
        <HugeiconsIcon
            strokeWidth={1.5}
            icon={Certificate01Icon}
            class="size-10 text-brand/60"
        />
    </div>
    <p class="text-center text-[12px] font-medium text-black">{title}</p>
    <div class="flex items-center justify-between text-[12px] text-[#7e7e7e]">
        <span>{clubName}</span>
        <span>{issuedAt}</span>
    </div>
    <p class="text-center text-[12px] text-[#aaa]" dir="ltr">{certificateNo}</p>
    <a
        href={downloadHref}
        class="mt-auto flex cursor-pointer items-center justify-center gap-1.5 rounded-[50px] bg-brand pt-[10px] pb-[8px] text-[12px] text-white transition-colors hover:bg-brand-dark"
        aria-label={t('dashboard_student.certificate_download')}
    >
        <HugeiconsIcon
            strokeWidth={2}
            icon={FileDownloadIcon}
            class="size-3.5"
        />
        <span>{t('app.download_pdf')}</span>
    </a>
</article>
