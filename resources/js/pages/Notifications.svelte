<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';

    type NotificationItem = {
        id: string;
        title: string;
        body: string;
        action_label: string;
        action_url: string | null;
        kind: string;
        read_at: string | null;
        created_at: string | null;
    };

    let {
        unreadNotifications = [],
        readNotifications = [],
    }: {
        unreadNotifications?: NotificationItem[];
        readNotifications?: NotificationItem[];
    } = $props();

    function markRead(id: string): void {
        router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
    }

    function markAllRead(): void {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    }

    function dateLabel(iso: string | null): string {
        if (!iso) {
            return '';
        }

        return formatDate(iso, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }
</script>

<AppHead title={t('notifications.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <section
            class="rounded-[24px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="space-y-2 text-start">
                    <h1 class="text-2xl font-semibold text-black">
                        {t('notifications.title')}
                    </h1>
                    <p class="text-sm text-[#7e7e7e]">
                        {t('notifications.subtitle')}
                    </p>
                </div>

                {#if unreadNotifications.length > 0}
                    <button
                        type="button"
                        onclick={markAllRead}
                        class="rounded-full bg-brand px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('notifications.mark_all_read')}
                    </button>
                {/if}
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('notifications.unread')} />

                {#if unreadNotifications.length === 0}
                    <EmptyState
                        message={t('notifications.empty')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each unreadNotifications as item (item.id)}
                            <div
                                class="rounded-[14px] border border-brand/15 bg-brand/5 p-4"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="space-y-1 text-start">
                                        <p
                                            class="text-sm font-medium text-black"
                                        >
                                            {item.title}
                                        </p>
                                        <p class="text-sm text-[#5f5f5f]">
                                            {item.body}
                                        </p>
                                        <p class="text-xs text-[#9a9a9a]">
                                            {dateLabel(item.created_at)}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        {#if item.action_url}
                                            <Link
                                                href={item.action_url}
                                                class="rounded-full bg-brand px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-brand-dark"
                                            >
                                                {item.action_label}
                                            </Link>
                                        {/if}
                                        <button
                                            type="button"
                                            onclick={() => markRead(item.id)}
                                            class="rounded-full bg-black/5 px-4 py-2 text-xs font-medium text-[#5f5f5f] transition-colors hover:bg-black/10"
                                        >
                                            {t('notifications.mark_read')}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('notifications.read')} />

                {#if readNotifications.length === 0}
                    <EmptyState
                        message={t('notifications.empty')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each readNotifications as item (item.id)}
                            <div
                                class="rounded-[14px] border border-black/10 p-4"
                            >
                                <div class="space-y-1 text-start">
                                    <p class="text-sm font-medium text-black">
                                        {item.title}
                                    </p>
                                    <p class="text-sm text-[#5f5f5f]">
                                        {item.body}
                                    </p>
                                    <div
                                        class="flex flex-wrap items-center gap-3 text-xs text-[#9a9a9a]"
                                    >
                                        <span>{dateLabel(item.created_at)}</span
                                        >
                                        {#if item.action_url}
                                            <Link
                                                href={item.action_url}
                                                class="text-brand transition-colors hover:text-brand-dark"
                                            >
                                                {t('notifications.open')}
                                            </Link>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        </section>
    </div>
</div>
