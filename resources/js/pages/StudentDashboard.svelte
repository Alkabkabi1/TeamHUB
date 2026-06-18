<script lang="ts">
    import {
        PlusSignIcon,
        MailAtSign02Icon,
        Calendar02Icon,
        CheckmarkCircle01Icon,
        CheckmarkBadge01Icon,
        StarIcon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import CertificateCard from '@/components/CertificateCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventCard from '@/components/EventCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import {
        Dialog,
        DialogContent,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { formatDate, formatNumber, t } from '@/lib/i18n.svelte';

    type Stat = {
        label: string;
        value: string;
        icon: IconSvgElement;
    };

    type ClubMembership = {
        name: string;
        memberSince: string;
        volunteerHours: number;
    };

    type DashboardStats = {
        clubsCount: number;
        eventsCount: number;
        certificatesCount: number;
        totalHours: number;
    };

    type Profile = {
        name: string;
        email: string;
        subtitle: string;
        joinedAt: string | null;
    };

    type Certificate = {
        id: number;
        certificateNo: string;
        eventTitle: string;
        clubName: string;
        issuedAt: string;
    };

    type FeaturedEvent = {
        id: number;
        title: string;
        description: string | null;
        startsAt: string | null;
        clubName: string;
        imageUrl: string | null;
    };

    type Props = {
        totalHours: number;
        stats: DashboardStats;
        clubs: ClubMembership[];
        profile: Profile;
        certificates: Certificate[];
        featuredEvents: FeaturedEvent[];
        qrSvg: string;
    };

    let {
        totalHours,
        stats,
        clubs,
        profile,
        certificates,
        featuredEvents,
        qrSvg,
    }: Props = $props();

    let qrOpen = $state(false);

    function formatEventDate(iso: string | null): string {
        if (!iso) {
            return '';
        }

        return formatDate(iso, {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    }

    // Hero join line, e.g. "انضم في سبتمبر 2022" (Figma node 40:2633).
    const joinedLabel = $derived(
        profile.joinedAt
            ? t('dashboard_student.joined_in', {
                  date: formatDate(profile.joinedAt, {
                      month: 'long',
                      year: 'numeric',
                  }),
              })
            : '',
    );

    const statCards: Stat[] = $derived([
        {
            label: t('dashboard_student.stats.clubs'),
            value: formatNumber(stats.clubsCount),
            icon: UserGroup03Icon,
        },
        {
            label: t('dashboard_student.stats.events'),
            value: formatNumber(stats.eventsCount),
            icon: CheckmarkCircle01Icon,
        },
        {
            label: t('dashboard_student.stats.certificates'),
            value: formatNumber(stats.certificatesCount),
            icon: CheckmarkBadge01Icon,
        },
        {
            label: t('dashboard_student.stats.hours'),
            value: formatNumber(stats.totalHours),
            icon: StarIcon,
        },
    ]);
</script>

<AppHead title={t('dashboard_student.title')} />

{#snippet metaItem(icon: IconSvgElement, text: string, ltr: boolean = false)}
    <span class="flex items-center gap-1.5">
        <HugeiconsIcon strokeWidth={2} {icon} class="size-3.5" />
        <span dir={ltr ? 'ltr' : undefined}>{text}</span>
    </span>
{/snippet}

<div class="flex flex-col">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <section aria-label={t('nav.profile')} class="w-full">
            <!-- Mobile / tablet hero -->
            <div
                class="relative h-[320px] w-full overflow-hidden rounded-[20px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:h-[380px] sm:rounded-[28px] lg:hidden"
            >
                <img
                    src="/images/hero/stars-mobile-left.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 left-0 h-full w-1/2"
                />
                <img
                    src="/images/hero/stars-mobile-right.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 right-0 h-full w-1/2"
                />

                <img
                    src="/images/hero/uqu-logo.png"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[-1%] left-1/2 h-[60%] -translate-x-1/2 object-contain opacity-[0.05]"
                />
                <img
                    src="/images/hero/uqu-logo.png"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[6%] left-1/2 h-[34%] -translate-x-1/2 object-contain"
                />

                <div
                    class="absolute inset-x-0 bottom-[8%] flex flex-col items-center gap-1.5 px-6 text-center"
                >
                    <p
                        class="text-[26px] leading-tight text-white sm:text-[32px]"
                    >
                        {profile.name}
                    </p>
                    {#if profile.subtitle}
                        <p
                            class="text-[14px] leading-snug text-white/80 sm:text-[16px]"
                        >
                            {profile.subtitle}
                        </p>
                    {/if}
                    <div
                        class="mt-1 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[12px] text-white/60"
                    >
                        {#if joinedLabel}
                            {@render metaItem(Calendar02Icon, joinedLabel)}
                        {/if}
                        {@render metaItem(
                            MailAtSign02Icon,
                            profile.email,
                            true,
                        )}
                    </div>
                    <p class="mt-1 text-[16px] text-white sm:text-[18px]">
                        {formatNumber(totalHours)}
                        {t('dashboard_student.hero_hours_unit')}
                    </p>
                </div>
            </div>

            <!-- Desktop hero - 1020 x 299 aspect, faithful to Figma node 40:2585 -->
            <div class="relative hidden aspect-[1020/299] w-full lg:block">
                <!-- Card background: Rectangle 5427 (1020 x 252 at y=24) -->
                <div
                    class="absolute inset-x-0 top-[8.03%] bottom-[7.69%] overflow-hidden rounded-[40px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                ></div>

                <!-- Faded watermark uqu-logo 2 (245 x 306 at x=768, y=-7) - opacity 0.04 -->
                <img
                    src="/images/hero/uqu-logo.png"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[-2.34%] right-[0.69%] aspect-[447/559] w-[24.02%] object-cover opacity-[0.04]"
                />
                <!-- Foreground uqu-logo 1 (132 x 165 at x=799, y=67) -->
                <img
                    src="/images/hero/uqu-logo.png"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[22.4%] right-[8.73%] aspect-[447/559] w-[12.94%] object-cover"
                />

                <!-- Stars cluster (221 x 299 at x=12) -->
                <img
                    src="/images/hero/stars.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-0 bottom-0 left-[1.18%] h-full w-[21.66%]"
                />

                <!-- Texts frame (right-aligned, ends at x=768 i.e. right-[24.7%]) -->
                <div
                    class="absolute top-[50%] right-[24.7%] flex w-[26.37%] -translate-y-1/2 flex-col items-start gap-1.5 text-start"
                >
                    <p class="w-full text-[40px] leading-[normal] text-white">
                        {profile.name}
                    </p>
                    {#if profile.subtitle}
                        <p
                            class="w-full text-[20px] leading-[normal] text-white/85"
                        >
                            {profile.subtitle}
                        </p>
                    {/if}
                    <div
                        class="mt-1 flex w-full items-center justify-start gap-4 text-[12px] text-white/60"
                    >
                        {#if joinedLabel}
                            {@render metaItem(Calendar02Icon, joinedLabel)}
                        {/if}
                        {@render metaItem(
                            MailAtSign02Icon,
                            profile.email,
                            true,
                        )}
                    </div>
                    <p class="mt-1 w-full text-[20px] text-white">
                        {formatNumber(totalHours)}
                        {t('dashboard_student.hero_hours_unit')}
                    </p>
                </div>
            </div>
        </section>

        <section aria-label={t('dashboard.overview')}>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {#each statCards as stat, i (i)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                    />
                {/each}
            </div>
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('dashboard_student.my_clubs')} />
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {#each clubs as club (club.name)}
                    <StatCard
                        icon={UserGroup03Icon}
                        label={club.name}
                        value={t('dashboard_student.member_since', {
                            year: club.memberSince,
                        })}
                        tone="list"
                        class="cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_32px_0_rgba(0,0,0,0.12)]"
                    >
                        {#snippet sub()}
                            <p class="mt-1 text-[12px] text-black/25">
                                <span class="text-brand/25">
                                    {club.volunteerHours}
                                </span>
                                <span> {t('dashboard.hours_unit')}</span>
                            </p>
                        {/snippet}
                        <span
                            class="flex shrink-0 items-center justify-center rounded-full bg-brand/25 px-2.5 pt-1.5 pb-1 text-[12px] text-brand"
                        >
                            {t('dashboard.status_active')}
                        </span>
                    </StatCard>
                {/each}
                <Link
                    href="/clubs"
                    class="flex h-[80px] cursor-pointer items-center justify-start gap-5 rounded-[10px] bg-white px-5 py-2.5 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_32px_0_rgba(0,0,0,0.12)]"
                >
                    <div
                        class="flex size-[50px] shrink-0 items-center justify-center rounded-full border-2 border-dashed border-black bg-brand/50 text-white shadow-[0_4px_12px_0_rgba(0,0,0,0.04)]"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={PlusSignIcon}
                            class="size-4"
                        />
                    </div>
                    <p class="text-start text-[12px] text-black">
                        {t('clubs.join')}
                    </p>
                </Link>
            </div>
        </section>

        <!-- My attendance QR — shown to a club scanner to log presence. -->
        <section class="flex flex-col gap-5">
            <SectionHeader title={t('dashboard_student.qr_title')} />
            <div
                class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-6 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:flex-row sm:items-center sm:gap-6 sm:text-start"
            >
                <button
                    type="button"
                    onclick={() => (qrOpen = true)}
                    aria-label={t('dashboard_student.qr_enlarge')}
                    class="size-40 shrink-0 cursor-pointer rounded-[14px] bg-white p-2 ring-1 ring-black/10 transition-transform hover:scale-[1.02] [&_svg]:size-full"
                >
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    {@html qrSvg}
                </button>
                <p class="max-w-md text-[14px] leading-relaxed text-[#5f5f5f]">
                    {t('dashboard_student.qr_hint')}
                </p>
            </div>
        </section>

        <!-- My Certificates Section (real data) -->
        <section class="flex flex-col gap-5">
            <SectionHeader title={t('dashboard_student.certificates')} />

            {#if certificates.length === 0}
                <div
                    class="flex min-h-[120px] items-center justify-center rounded-[20px] bg-white shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <p class="text-sm text-[#7e7e7e]">
                        {t('dashboard_student.no_certificates')}
                    </p>
                </div>
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {#each certificates as cert (cert.id)}
                        <CertificateCard
                            title={cert.eventTitle}
                            clubName={cert.clubName}
                            issuedAt={cert.issuedAt}
                            certificateNo={cert.certificateNo}
                            downloadHref={`/certificates/${cert.id}/download`}
                        />
                    {/each}
                </div>
            {/if}
        </section>

        <!-- Featured events you may like -->
        <section class="flex flex-col gap-5">
            <SectionHeader
                title={t('dashboard_student.featured_events_interest')}
                href="/events"
            />

            {#if featuredEvents.length === 0}
                <EmptyState
                    class="shadow-[0_8px_24px_0_rgba(0,0,0,0.08)]"
                    message={t('events.no_events')}
                />
            {:else}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    {#each featuredEvents as event (event.id)}
                        <EventCard
                            title={event.title}
                            metaStart={formatEventDate(event.startsAt)}
                            metaEnd={event.clubName}
                            description={event.description}
                            imageUrl={event.imageUrl}
                            href={`/events/${event.id}`}
                        />
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>

<!-- Enlarged attendance QR for easier scanning. -->
<Dialog bind:open={qrOpen}>
    <DialogContent class="sm:max-w-sm">
        <DialogHeader>
            <DialogTitle>{t('dashboard_student.qr_title')}</DialogTitle>
        </DialogHeader>
        <div
            class="mx-auto w-full max-w-[320px] rounded-[14px] bg-white p-4 [&_svg]:size-full"
        >
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html qrSvg}
        </div>
    </DialogContent>
</Dialog>
