<script lang="ts">
    import { UserGroup03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import { show as clubsShow } from '@/routes/clubs';
    import type { ClubListItem } from '@/types';

    let {
        canRegister: _canRegister = true,
        clubs = [],
    }: {
        canRegister?: boolean;
        clubs?: ClubListItem[];
    } = $props();
</script>

<AppHead title={t('welcome.title')} />

<div class="flex flex-col">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={t('welcome.uqu_section_aria')}
            title={t('welcome.uqu_title')}
            subtitle={t('welcome.uqu_subtitle')}
        />

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('welcome.featured_clubs')} href="/clubs" />
            {#if clubs.length === 0}
                <EmptyState
                    class="rounded-[10px] py-6 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)]"
                    message={t('clubs.no_clubs')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each clubs as club (club.id)}
                        <article
                            class="relative flex h-[60px] items-center justify-between rounded-[10px] bg-white px-5 py-2.5 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_32px_0_rgba(0,0,0,0.12)]"
                        >
                            <Link
                                href={clubsShow(club).url}
                                class="absolute inset-0 rounded-[10px]"
                                aria-label={club.name}
                            ></Link>
                            <div
                                class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand/50 text-white shadow-[0_4px_12px_0_rgba(0,0,0,0.04)]"
                            >
                                {#if club.logo_url}
                                    <img
                                        src={club.logo_url}
                                        alt=""
                                        class="h-full w-full object-cover"
                                    />
                                {:else}
                                    <HugeiconsIcon
                                        strokeWidth={2}
                                        icon={UserGroup03Icon}
                                        class="size-4"
                                    />
                                {/if}
                            </div>
                            <div
                                class="flex min-w-0 flex-1 flex-col items-end px-3 text-start leading-none"
                            >
                                <p
                                    class="w-full truncate text-[12px] text-black"
                                >
                                    {club.name}
                                </p>
                                <p
                                    class="mt-1 w-full truncate text-[12px] text-[#7e7e7e]"
                                >
                                    {t('app.members_count', {
                                        count: formatNumber(club.members_count),
                                    })}
                                </p>
                            </div>
                            {#if !club.is_member}
                                <Link
                                    href={`/clubs/${club.id}/join`}
                                    class="relative z-10 flex shrink-0 cursor-pointer items-center justify-center rounded-full bg-brand/50 px-2.5 pt-1.5 pb-1 text-[12px] text-white transition-colors hover:bg-brand/70"
                                >
                                    {t('clubs.join')}
                                </Link>
                            {/if}
                        </article>
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>
