<script lang="ts">
    import {
        ArrowRight01Icon,
        Calendar03Icon,
        Clock01Icon,
        Location01Icon,
        QrCodeIcon,
        Settings01Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ImageGallery from '@/components/ImageGallery.svelte';
    import {
        formatDate as formatLocalizedDate,
        formatNumber,
        t,
    } from '@/lib/i18n.svelte';
    import { login, home } from '@/routes';
    import type { EventDetail } from '@/types';

    let {
        event,
        isRegistered = false,
        canManage = false,
        canScan = false,
    }: {
        event: EventDetail;
        isRegistered?: boolean;
        canManage?: boolean;
        canScan?: boolean;
    } = $props();

    function formatDateTime(value: string): string {
        return formatLocalizedDate(value, {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function formatTime(value: string): string {
        return formatLocalizedDate(value, {
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    const isGuest = $derived(!page.props.auth?.user);
    const isStudent = $derived(page.props.auth?.user?.role === 'student');
    const demoQuickLogin = $derived(
        Boolean(
            (page.props.demo as { quick_login?: boolean } | undefined)
                ?.quick_login,
        ),
    );
    const guestRsvpHref = $derived(demoQuickLogin ? home() : login());

    // Guests and students both get a registration CTA; guests pick a demo role
    // or sign in first, then return here via intended redirect when applicable.
    const canAttemptRsvp = $derived(isGuest || isStudent);

    const registrationLabel = $derived(
        event.capacity
            ? t('events.managed.registrations_of', {
                  count: formatNumber(event.registrations_count),
                  total: formatNumber(event.capacity),
              })
            : t('events.managed.registrations', {
                  count: formatNumber(event.registrations_count),
              }),
    );

    const isCapacityFull = $derived(event.is_full && !isRegistered);

    function handleRsvp(): void {
        if (isRegistered) {
            router.delete(`/events/${event.id}/rsvp`, { preserveScroll: true });
        } else {
            router.post(
                `/events/${event.id}/rsvp`,
                {},
                { preserveScroll: true },
            );
        }
    }
</script>

<AppHead title={event.title} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <Link
            href="/events"
            class="flex w-fit items-center gap-1.5 text-sm text-[#7e7e7e] transition-colors hover:text-brand"
        >
            <HugeiconsIcon
                strokeWidth={2}
                icon={ArrowRight01Icon}
                class="size-4 rotate-180 rtl:rotate-0"
            />
            <span>{t('events.show.back')}</span>
        </Link>

        <!-- Cover gallery -->
        <div class="relative">
            <ImageGallery
                images={event.images}
                alt={event.title}
                class="aspect-video shadow-[8px_8px_48px_rgba(0,0,0,0.08)] sm:aspect-[21/9]"
            />
            {#if event.status !== 'active'}
                <span
                    class="absolute end-4 top-4 rounded-full bg-black/60 px-4 py-1.5 text-[12px] font-medium text-white"
                >
                    {t(`events.status_labels.${event.status}`)}
                </span>
            {/if}
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
            <!-- Main column -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2 text-start">
                    <Link
                        href={`/clubs/${event.club.id}`}
                        class="w-fit text-[13px] text-brand transition-colors hover:text-brand-dark"
                    >
                        {event.club.name}
                    </Link>
                    <h1 class="text-2xl font-bold text-black sm:text-3xl">
                        {event.title}
                    </h1>
                </div>

                <section class="flex flex-col gap-3 text-start">
                    <h2 class="text-lg text-[#5f5f5f]">
                        {t('events.show.about')}
                    </h2>
                    <p
                        class="text-sm leading-7 whitespace-pre-line text-[#7e7e7e]"
                    >
                        {event.description ?? t('events.details_soon')}
                    </p>
                </section>
            </div>

            <!-- Sidebar -->
            <aside
                class="flex h-fit flex-col gap-5 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
            >
                <div class="flex items-start gap-3">
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Calendar03Icon}
                        class="mt-0.5 size-5 shrink-0 text-brand"
                    />
                    <div class="flex flex-col text-start">
                        <span class="text-[12px] text-[#7e7e7e]"
                            >{t('events.show.when')}</span
                        >
                        <span class="text-[13px] text-black"
                            >{formatDateTime(event.starts_at)}</span
                        >
                        <span
                            class="flex items-center gap-1 text-[12px] text-[#7e7e7e]"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={Clock01Icon}
                                class="size-3.5"
                            />
                            {t('events.show.to')}
                            {formatTime(event.ends_at)}
                        </span>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Location01Icon}
                        class="mt-0.5 size-5 shrink-0 text-brand"
                    />
                    <div class="flex flex-col text-start">
                        <span class="text-[12px] text-[#7e7e7e]"
                            >{t('events.show.where')}</span
                        >
                        <span class="text-[13px] text-black"
                            >{event.location ?? t('events.location_tbd')}</span
                        >
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={UserGroup03Icon}
                        class="mt-0.5 size-5 shrink-0 text-brand"
                    />
                    <div class="flex flex-col text-start">
                        <span class="text-[12px] text-[#7e7e7e]"
                            >{t('events.show.capacity')}</span
                        >
                        <span class="text-[13px] text-black"
                            >{registrationLabel}</span
                        >
                    </div>
                </div>

                {#if canAttemptRsvp}
                    {#if isCapacityFull}
                        <span
                            class="rounded-full bg-[#f13e3e]/10 px-4 py-2.5 text-center text-[13px] font-medium text-[#f13e3e]"
                        >
                            {t('events.capacity_full')}
                        </span>
                    {:else if !event.is_open && !isRegistered}
                        <span
                            class="rounded-full bg-black/5 px-4 py-2.5 text-center text-[13px] font-medium text-[#7e7e7e]"
                        >
                            {t('events.show.registration_closed')}
                        </span>
                    {:else if isGuest}
                        <Link
                            href={guestRsvpHref}
                            class="rounded-full bg-brand px-4 py-2.5 text-center text-[13px] font-medium text-white transition-colors hover:bg-brand-dark"
                        >
                            {t('events.rsvp_register')}
                        </Link>
                    {:else}
                        <button
                            type="button"
                            onclick={handleRsvp}
                            class={isRegistered
                                ? 'rounded-full bg-brand/20 px-4 py-2.5 text-[13px] font-medium text-brand transition-colors hover:bg-brand/30'
                                : 'rounded-full bg-brand px-4 py-2.5 text-[13px] font-medium text-white transition-colors hover:bg-brand-dark'}
                        >
                            {isRegistered
                                ? t('events.rsvp_cancel')
                                : t('events.rsvp_register')}
                        </button>
                    {/if}
                {/if}

                {#if canScan}
                    <Link
                        href={`/clubs/${event.club.id}/events/${event.id}/scan`}
                        class="flex items-center justify-center gap-1.5 rounded-full bg-brand px-4 py-2.5 text-[13px] font-medium text-white transition-colors hover:bg-brand-dark"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={QrCodeIcon}
                            class="size-4"
                        />
                        {t('attendance.manage.scan_button')}
                    </Link>
                {/if}

                {#if canManage}
                    <Link
                        href={`/clubs/${event.club.id}/events/${event.id}/edit`}
                        class="flex items-center justify-center gap-1.5 rounded-full bg-brand/10 px-4 py-2.5 text-[13px] font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={Settings01Icon}
                            class="size-4"
                        />
                        {t('events.show.edit')}
                    </Link>
                {/if}

                <Link
                    href={`/clubs/${event.club.id}`}
                    class="text-center text-[12px] text-[#7e7e7e] transition-colors hover:text-brand"
                >
                    {t('events.show.visit_club')}
                </Link>
            </aside>
        </div>
    </div>
</div>
