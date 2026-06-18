<script lang="ts">
    import {
        ArrowRight01Icon,
        CheckmarkCircle02Icon,
        Location01Icon,
        QrCodeIcon,
        UserGroupIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, useHttp } from '@inertiajs/svelte';
    import { Html5Qrcode } from 'html5-qrcode';
    import { onDestroy, untrack } from 'svelte';
    import { checkIn as checkInAction } from '@/actions/App/Http/Controllers/AttendanceController';
    import AppHead from '@/components/AppHead.svelte';
    import { formatDate, formatNumber, t } from '@/lib/i18n.svelte';

    type RosterEntry = {
        userId: number;
        name: string;
        email: string;
        daysAttended: number;
        checkedInToday: boolean;
    };

    type CheckInResult = 'checked_in' | 'already_today' | 'invalid';

    type CheckInResponse = {
        result: CheckInResult;
        studentName?: string;
        userId?: number;
        wasWalkIn?: boolean;
        daysAttended?: number;
    };

    let {
        club,
        event,
        registered = [],
    }: {
        club: { id: number; name: string };
        event: {
            id: number;
            title: string;
            starts_at: string | null;
            ends_at: string | null;
            location: string | null;
        };
        registered: RosterEntry[];
    } = $props();

    // Local, mutable copy of the server roster so successful scans can update
    // each entry's badge in place without a round-trip (a one-time snapshot).
    let roster = $state<RosterEntry[]>(
        untrack(() => registered.map((entry) => ({ ...entry }))),
    );
    let scanning = $state(false);
    let cameraError = $state(false);
    let feedback = $state<{ type: CheckInResult; message: string } | null>(
        null,
    );

    const checkInHttp = useHttp<{ qr_token: string }, CheckInResponse>({
        qr_token: '',
    });

    const checkedInTodayCount = $derived(
        roster.filter((entry) => entry.checkedInToday).length,
    );

    let scanner: Html5Qrcode | null = null;
    let lastToken = '';
    let lastAt = 0;
    let feedbackTimer: ReturnType<typeof setTimeout> | undefined;

    function formatRange(): string {
        if (event.starts_at === null) {
            return '';
        }

        const opts: Intl.DateTimeFormatOptions = {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        };

        const start = formatDate(event.starts_at, opts);

        if (event.ends_at === null) {
            return start;
        }

        const end = formatDate(event.ends_at, opts);

        return start === end ? start : `${start} – ${end}`;
    }

    function showFeedback(type: CheckInResult, message: string): void {
        feedback = { type, message };
        clearTimeout(feedbackTimer);
        feedbackTimer = setTimeout(() => {
            feedback = null;
        }, 4000);
    }

    async function submitToken(token: string): Promise<void> {
        // Ignore the rapid repeated decodes html5-qrcode emits for one code.
        const now = Date.now();

        if (token === lastToken && now - lastAt < 3000) {
            return;
        }

        lastToken = token;
        lastAt = now;

        checkInHttp.qr_token = token;

        const response = await checkInHttp.post(
            checkInAction.url({ club: club.id, event: event.id }),
        );

        applyResult(response);
    }

    function applyResult(response: CheckInResponse): void {
        const name = response.studentName ?? '';

        if (
            response.result === 'checked_in' ||
            response.result === 'already_today'
        ) {
            const known = roster.some(
                (entry) => entry.userId === response.userId,
            );

            if (known) {
                roster = roster.map((entry) =>
                    entry.userId === response.userId
                        ? {
                              ...entry,
                              checkedInToday: true,
                              daysAttended:
                                  response.daysAttended ?? entry.daysAttended,
                          }
                        : entry,
                );
            } else if (response.userId !== undefined) {
                // A walk-in who never registered — surface them in the roster.
                roster = [
                    {
                        userId: response.userId,
                        name,
                        email: '',
                        daysAttended: response.daysAttended ?? 1,
                        checkedInToday: true,
                    },
                    ...roster,
                ];
            }
        }

        // A walk-in is still a successful check-in; flag it so the supervisor
        // knows the student had not registered beforehand.
        const messageKey =
            response.result === 'checked_in' && response.wasWalkIn
                ? 'attendance.scan.result.walk_in'
                : `attendance.scan.result.${response.result}`;

        showFeedback(response.result, t(messageKey, { name }));
    }

    async function startScanning(): Promise<void> {
        cameraError = false;

        try {
            scanner = new Html5Qrcode('qr-reader');
            await scanner.start(
                { facingMode: 'environment' },
                // Omit `qrbox` so the whole camera frame is scanned instead of a
                // fixed centred box; `aspectRatio: 1` requests a square stream so
                // the video fills the square viewport on mobile without a letterbox.
                { fps: 10, aspectRatio: 1 },
                (decodedText) => {
                    void submitToken(decodedText);
                },
                undefined,
            );
            scanning = true;
        } catch {
            cameraError = true;
            scanner = null;
        }
    }

    async function stopScanning(): Promise<void> {
        if (scanner !== null) {
            try {
                await scanner.stop();
                scanner.clear();
            } catch {
                // Camera may already be stopped; ignore.
            }

            scanner = null;
        }

        scanning = false;
    }

    onDestroy(() => {
        clearTimeout(feedbackTimer);
        void stopScanning();
    });
</script>

<AppHead title={t('attendance.scan.title')} />

<div class="flex min-h-full flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <Link
            href={`/clubs/${club.id}/manage`}
            class="flex w-fit items-center gap-1.5 text-sm text-[#7e7e7e] transition-colors hover:text-brand"
        >
            <HugeiconsIcon
                strokeWidth={2}
                icon={ArrowRight01Icon}
                class="size-4 rotate-180 rtl:rotate-0"
            />
            <span>{t('attendance.scan.back')}</span>
        </Link>

        <header class="flex flex-col gap-2">
            <h1 class="text-start text-2xl text-black sm:text-3xl">
                {event.title}
            </h1>
            <p class="text-start text-sm text-[#5f5f5f]">
                {t('attendance.scan.subtitle')}
            </p>
            <div
                class="mt-1 flex flex-wrap items-center gap-4 text-[13px] text-[#7e7e7e]"
            >
                {#if formatRange()}
                    <span>{formatRange()}</span>
                {/if}
                {#if event.location}
                    <span class="flex items-center gap-1.5">
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={Location01Icon}
                            class="size-4"
                        />
                        {event.location}
                    </span>
                {/if}
                <span class="flex items-center gap-1.5">
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={UserGroupIcon}
                        class="size-4"
                    />
                    {t('attendance.scan.checked_in_today_count', {
                        count: formatNumber(checkedInTodayCount),
                    })}
                </span>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Scanner -->
            <section class="flex flex-col gap-4">
                <div
                    class="overflow-hidden rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <div
                        id="qr-reader"
                        class="mx-auto aspect-square w-full max-w-[360px] overflow-hidden rounded-[14px] bg-black/5"
                    >
                        {#if !scanning}
                            <div
                                class="flex h-full flex-col items-center justify-center gap-3 text-[#7e7e7e]"
                            >
                                <HugeiconsIcon
                                    strokeWidth={1.5}
                                    icon={QrCodeIcon}
                                    class="size-16 text-brand/40"
                                />
                                <p class="px-6 text-center text-[13px]">
                                    {t('attendance.scan.camera_hint')}
                                </p>
                            </div>
                        {/if}
                    </div>

                    {#if cameraError}
                        <p
                            class="mt-3 rounded-[10px] bg-[#f13e3e]/10 px-4 py-2.5 text-center text-[13px] text-[#f13e3e]"
                        >
                            {t('attendance.scan.camera_error')}
                        </p>
                    {/if}

                    <div class="mt-4 flex justify-center">
                        {#if scanning}
                            <button
                                type="button"
                                onclick={() => void stopScanning()}
                                class="cursor-pointer rounded-full bg-black/5 px-8 py-2.5 text-[13px] text-[#5f5f5f] transition-colors hover:bg-black/10"
                            >
                                {t('attendance.scan.stop_camera')}
                            </button>
                        {:else}
                            <button
                                type="button"
                                onclick={() => void startScanning()}
                                class="cursor-pointer rounded-full bg-brand px-8 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                            >
                                {t('attendance.scan.start_camera')}
                            </button>
                        {/if}
                    </div>
                </div>

                {#if feedback}
                    <div
                        class={'flex items-center gap-2.5 rounded-[14px] px-5 py-3.5 text-[14px] ' +
                            (feedback.type === 'checked_in'
                                ? 'bg-brand/10 text-brand'
                                : feedback.type === 'already_today'
                                  ? 'bg-[#e9a23b]/15 text-[#a96a12]'
                                  : 'bg-[#f13e3e]/10 text-[#f13e3e]')}
                        role="status"
                        aria-live="polite"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={CheckmarkCircle02Icon}
                            class="size-5 shrink-0"
                        />
                        <span>{feedback.message}</span>
                    </div>
                {/if}
            </section>

            <!-- Live roster -->
            <section
                class="flex flex-col gap-4 rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                <h2 class="text-start text-[15px] text-black">
                    {t('attendance.scan.roster_title')}
                </h2>
                {#if roster.length === 0}
                    <p class="text-start text-[13px] text-[#7e7e7e]">
                        {t('attendance.scan.roster_empty')}
                    </p>
                {:else}
                    <ul class="flex flex-col gap-2">
                        {#each roster as entry (entry.userId)}
                            <li
                                class="flex items-center justify-between gap-3 rounded-[12px] border border-black/10 px-4 py-2.5"
                            >
                                <div
                                    class="flex min-w-0 flex-col items-start leading-tight"
                                >
                                    <span class="text-[13px] text-black"
                                        >{entry.name}</span
                                    >
                                    <span class="text-[11px] text-[#7e7e7e]">
                                        {t('attendance.scan.days_attended', {
                                            count: formatNumber(
                                                entry.daysAttended,
                                            ),
                                        })}
                                    </span>
                                </div>
                                {#if entry.checkedInToday}
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-brand/15 px-3 py-1 text-[11px] text-brand"
                                    >
                                        <HugeiconsIcon
                                            strokeWidth={2}
                                            icon={CheckmarkCircle02Icon}
                                            class="size-3.5"
                                        />
                                        {t('attendance.scan.checked_in_today')}
                                    </span>
                                {:else}
                                    <span
                                        class="shrink-0 rounded-full bg-black/5 px-3 py-1 text-[11px] text-[#7e7e7e]"
                                    >
                                        {t('attendance.scan.not_checked_in')}
                                    </span>
                                {/if}
                            </li>
                        {/each}
                    </ul>
                {/if}
            </section>
        </div>
    </div>
</div>

<style>
    /*
     * html5-qrcode injects a <video> sized to the camera's native aspect ratio,
     * which leaves a rectangular letterbox inside our square viewport. Force it
     * to cover the full square so the whole camera area is visible. :global is
     * required because the element is added to the DOM at runtime.
     */
    :global(#qr-reader video) {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    /* Hide the library's default shaded-region overlay if it appears. */
    :global(#qr-reader__scan_region img) {
        display: none;
    }
</style>
