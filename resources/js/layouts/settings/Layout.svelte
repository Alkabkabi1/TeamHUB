<script lang="ts">
    import type { Snippet } from 'svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebarNavItems = $derived<NavItem[]>([
        {
            title: t('settings.nav_profile'),
            href: editProfile(),
        },
        {
            title: t('settings.nav_security'),
            href: editSecurity(),
        },
    ]);

    const url = currentUrlState();
</script>

<div class="px-4 py-6">
    <Heading
        title={t('settings.settings_heading')}
        description={t('settings.settings_description')}
    />

    <div class="flex flex-col lg:flex-row lg:space-x-12">
        <aside class="w-full max-w-xl lg:w-48">
            <nav
                class="flex flex-col space-y-1 space-x-0"
                aria-label={t('settings.nav_settings')}
            >
                {#each sidebarNavItems as item (toUrl(item.href))}
                    <Button
                        variant="ghost"
                        href={toUrl(item.href)}
                        class="w-full justify-start {url.isCurrentUrl(
                            item.href,
                            url.currentUrl,
                        )
                            ? 'bg-muted'
                            : ''}"
                    >
                        {item.title}
                    </Button>
                {/each}
            </nav>
        </aside>

        <Separator class="my-6 lg:hidden" />

        <div class="flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                {@render children?.()}
            </section>
        </div>
    </div>
</div>
