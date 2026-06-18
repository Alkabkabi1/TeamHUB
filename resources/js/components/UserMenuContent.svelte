<script lang="ts">
    import { Logout01Icon, Settings01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, router } from '@inertiajs/svelte';
    import {
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
    } from '@/components/ui/dropdown-menu';
    import UserInfo from '@/components/UserInfo.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { toUrl } from '@/lib/utils';
    import { logout } from '@/routes';
    import { edit } from '@/routes/profile';
    import type { User } from '@/types';

    let {
        user,
    }: {
        user: User;
    } = $props();

    function handleLogout(propsOnClick?: () => void) {
        return () => {
            propsOnClick?.();
            router.flushAll();
        };
    }
</script>

<DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <UserInfo {user} showEmail={true} />
    </div>
</DropdownMenuLabel>
<DropdownMenuSeparator />
<DropdownMenuGroup>
    <DropdownMenuItem>
        {#snippet child({ props })}
            <Link href={toUrl(edit())} prefetch {...props}>
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Settings01Icon}
                    class="mr-2 h-4 w-4"
                />
                {t('settings.nav_settings')}
            </Link>
        {/snippet}
    </DropdownMenuItem>
</DropdownMenuGroup>
<DropdownMenuSeparator />
<DropdownMenuItem>
    {#snippet child({ props })}
        <Link
            href={logout()}
            as="button"
            {...props}
            onclick={handleLogout(props.onclick)}
            data-test="logout-button"
        >
            <HugeiconsIcon
                strokeWidth={2}
                icon={Logout01Icon}
                class="mr-2 h-4 w-4"
            />
            {t('nav.logout')}
        </Link>
    {/snippet}
</DropdownMenuItem>
