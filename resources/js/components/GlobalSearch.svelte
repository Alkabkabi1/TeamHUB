<script lang="ts">
    import {
        AiSchedulingIcon,
        BookDownloadIcon,
        DashboardBrowsingIcon,
        Home03Icon,
        News01Icon,
        UserCircleIcon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { page, router } from '@inertiajs/svelte';
    import * as Command from '@/components/ui/command';
    import { globalSearch, openGlobalSearch } from '@/lib/globalSearch.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import {
        clubs,
        events,
        home,
        resources,
        search,
        studentDashboard,
    } from '@/routes';

    type SearchItem = {
        id: number;
        title: string;
        subtitle: string;
        url: string;
    };

    type SearchGroups = {
        clubs: SearchItem[];
        events: SearchItem[];
        news: SearchItem[];
        resources: SearchItem[];
    };

    const emptyGroups: SearchGroups = {
        clubs: [],
        events: [],
        news: [],
        resources: [],
    };

    const direction = $derived((page.props.direction as string) ?? 'rtl');
    const auth = $derived(page.props.auth);
    const role = $derived(auth?.user?.role as string | undefined);

    let query = $state('');
    let loading = $state(false);
    let groups = $state<SearchGroups>(emptyGroups);

    const term = $derived(query.trim());
    const hasResults = $derived(
        groups.clubs.length > 0 ||
            groups.events.length > 0 ||
            groups.news.length > 0 ||
            groups.resources.length > 0,
    );

    type QuickLink = { title: string; href: string; icon: IconSvgElement };

    const quickLinks = $derived<QuickLink[]>([
        { title: t('nav.home'), href: toUrl(home()), icon: Home03Icon },
        { title: t('nav.clubs'), href: toUrl(clubs()), icon: UserGroup03Icon },
        {
            title: t('nav.events'),
            href: toUrl(events()),
            icon: AiSchedulingIcon,
        },
        { title: t('nav.news'), href: '/news', icon: News01Icon },
        {
            title: t('nav.resources'),
            href: toUrl(resources()),
            icon: BookDownloadIcon,
        },
        ...(role === 'student'
            ? [
                  {
                      title: t('nav.student_account'),
                      href: toUrl(studentDashboard()),
                      icon: UserCircleIcon,
                  },
              ]
            : []),
        ...(role === 'university_staff'
            ? [
                  {
                      title: t('nav.admin_dashboard'),
                      href: '/admin',
                      icon: DashboardBrowsingIcon,
                  },
              ]
            : []),
    ]);

    const resultGroups = $derived([
        {
            key: 'clubs',
            heading: t('app.search_group_clubs'),
            icon: UserGroup03Icon,
        },
        {
            key: 'events',
            heading: t('app.search_group_events'),
            icon: AiSchedulingIcon,
        },
        { key: 'news', heading: t('app.search_group_news'), icon: News01Icon },
        {
            key: 'resources',
            heading: t('app.search_group_resources'),
            icon: BookDownloadIcon,
        },
    ] as const);

    // Debounced, cancellable lookup. Re-runs whenever the trimmed term changes;
    // the cleanup aborts any in-flight request and clears the pending timer.
    $effect(() => {
        if (term.length < 2) {
            groups = emptyGroups;
            loading = false;

            return;
        }

        loading = true;
        const controller = new AbortController();

        const timer = window.setTimeout(() => {
            fetch(search.url({ query: { q: term } }), {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((data: { groups: SearchGroups }) => {
                    groups = data.groups ?? emptyGroups;
                })
                .catch((error: unknown) => {
                    if (
                        !(error instanceof DOMException) ||
                        error.name !== 'AbortError'
                    ) {
                        groups = emptyGroups;
                    }
                })
                .finally(() => {
                    loading = false;
                });
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    });

    // Reset when the palette closes so it reopens clean.
    $effect(() => {
        if (!globalSearch.open) {
            query = '';
            groups = emptyGroups;
        }
    });

    // ⌘K / Ctrl+K opens the palette from anywhere.
    $effect(() => {
        function onKeydown(event: KeyboardEvent): void {
            if (event.key === 'k' && (event.metaKey || event.ctrlKey)) {
                event.preventDefault();
                openGlobalSearch();
            }
        }

        window.addEventListener('keydown', onKeydown);

        return () => window.removeEventListener('keydown', onKeydown);
    });

    function go(url: string): void {
        globalSearch.open = false;
        router.visit(url);
    }
</script>

<Command.Dialog
    bind:open={globalSearch.open}
    dir={direction}
    shouldFilter={false}
    title={t('app.search')}
    description={t('app.search_dialog_placeholder')}
>
    <Command.Input
        bind:value={query}
        placeholder={t('app.search_dialog_placeholder')}
    />
    <Command.List>
        {#if term.length < 2}
            <Command.Group heading={t('app.search_quick_nav')}>
                {#each quickLinks as link (link.href)}
                    <Command.Item
                        value={`nav:${link.href}`}
                        onSelect={() => go(link.href)}
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={link.icon}
                            class="size-4"
                        />
                        <span>{link.title}</span>
                    </Command.Item>
                {/each}
            </Command.Group>
        {:else}
            {#each resultGroups as group (group.key)}
                {#if groups[group.key].length > 0}
                    <Command.Group heading={group.heading}>
                        {#each groups[group.key] as item (item.id)}
                            <Command.Item
                                value={`${group.key}:${item.id}`}
                                onSelect={() => go(item.url)}
                            >
                                <HugeiconsIcon
                                    strokeWidth={2}
                                    icon={group.icon}
                                    class="size-4"
                                />
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate">{item.title}</span>
                                    {#if item.subtitle}
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {item.subtitle}
                                        </span>
                                    {/if}
                                </div>
                            </Command.Item>
                        {/each}
                    </Command.Group>
                {/if}
            {/each}

            {#if loading}
                <div class="py-6 text-center text-sm text-muted-foreground">
                    {t('app.loading')}
                </div>
            {:else if !hasResults}
                <div class="py-6 text-center text-sm text-muted-foreground">
                    {t('app.search_no_results')}
                </div>
            {/if}
        {/if}
    </Command.List>
</Command.Dialog>
