<script lang="ts">
    import { ArrowLeft01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { router } from '@inertiajs/svelte';
    import { page } from '@inertiajs/svelte';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n.svelte';
    import { login as demoLogin } from '@/routes/demo';

    type DemoAccount = {
        email: string;
        name: string;
        role: string;
        label?: string;
    };

    let {
        accounts = [],
        variant = 'compact',
    }: {
        accounts?: DemoAccount[];
        variant?: 'cards' | 'compact' | 'toggles';
    } = $props();

    const currentEmail = $derived(page.props.auth?.user?.email ?? null);

    let submitting = $state<string | null>(null);

    const demo = $derived(
        page.props.demo as
            | { quick_login: boolean; accounts: DemoAccount[] }
            | undefined,
    );
    const items = $derived(
        accounts.length > 0 ? accounts : (demo?.accounts ?? []),
    );

    function chooseRole(email: string): void {
        submitting = email;
        router.post(
            demoLogin.url(),
            { email },
            { onFinish: () => (submitting = null) },
        );
    }

    function isActive(account: DemoAccount): boolean {
        return currentEmail === account.email || submitting === account.email;
    }

    function roleLabel(account: DemoAccount): string {
        return account.label ?? t(`auth.demo_roles.${account.role}`);
    }
</script>

{#if items.length > 0}
    {#if variant === 'toggles'}
        <div
            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-center"
            role="group"
            aria-label={t('hub.entry_title')}
        >
            {#each items as account (account.email)}
                <button
                    type="button"
                    onclick={() => chooseRole(account.email)}
                    disabled={submitting !== null}
                    aria-pressed={isActive(account)}
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-all disabled:opacity-60 sm:min-w-[7rem] sm:w-auto"
                    style={isActive(account)
                        ? 'border-color: var(--th-primary); background: var(--th-primary); color: #fff'
                        : 'border-color: var(--th-border); background: transparent; color: var(--th-text)'}
                >
                    {#if submitting === account.email}
                        <Spinner class="size-4" />
                    {/if}
                    {roleLabel(account)}
                </button>
            {/each}
        </div>
    {:else if variant === 'cards'}
        <div class="grid gap-3 sm:grid-cols-2">
            {#each items as account (account.email)}
                <button
                    type="button"
                    onclick={() => chooseRole(account.email)}
                    disabled={submitting !== null}
                    class="group flex items-start gap-3 rounded-2xl border border-brand/15 bg-white/80 p-4 text-start shadow-sm transition-all hover:border-brand/40 hover:bg-white hover:shadow-md disabled:opacity-60"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-base font-semibold text-brand"
                    >
                        {account.name.charAt(0)}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-sm font-semibold text-black"
                        >
                            {account.name}
                        </span>
                        <span class="mt-0.5 block text-xs text-[#7e7e7e]">
                            {roleLabel(account)}
                        </span>
                    </span>
                    {#if submitting === account.email}
                        <Spinner class="size-4 text-brand" />
                    {:else}
                        <HugeiconsIcon
                            icon={ArrowLeft01Icon}
                            strokeWidth={2}
                            class="size-4 shrink-0 text-[#c4c4c4] transition-colors group-hover:text-brand"
                        />
                    {/if}
                </button>
            {/each}
        </div>
    {:else}
        <div class="space-y-1">
            {#each items as account (account.email)}
                <button
                    type="button"
                    onclick={() => chooseRole(account.email)}
                    disabled={submitting !== null}
                    class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-xs transition-colors th-hover disabled:opacity-60"
                    style="color: var(--th-text)"
                >
                    <span
                        class="flex size-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold"
                        style="background: color-mix(in srgb, var(--th-primary) 15%, transparent); color: var(--th-primary)"
                    >
                        {account.name.charAt(0)}
                    </span>
                    <span class="min-w-0 flex-1 truncate">{account.name}</span>
                    <span
                        class="truncate text-[10px]"
                        style="color: var(--th-text-muted)"
                    >
                        {roleLabel(account)}
                    </span>
                    {#if submitting === account.email}
                        <Spinner class="size-3" />
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
{/if}
