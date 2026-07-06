<script module lang="ts">
    // Opt out of the default AuthLayout — this page renders its own full-screen design.
    export const layout = () => null;
</script>

<script lang="ts">
    import {
        ArrowLeft01Icon,
        MagicWand01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Form, Link, router } from '@inertiajs/svelte';
    import { slide } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import AppAuthLayout from '@/layouts/auth/AppAuthLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { register } from '@/routes';
    import { login as demoLogin } from '@/routes/demo';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    type DemoAccount = { email: string; name: string; role: string };

    let {
        status = '',
        canResetPassword,
        canRegister,
        demoAccounts = [],
    }: {
        status?: string;
        canResetPassword: boolean;
        canRegister: boolean;
        demoAccounts?: DemoAccount[];
    } = $props();

    let demoOpen = $state(demoAccounts.length > 0);
    let demoSubmitting = $state<string | null>(null);

    function loginAsDemo(email: string): void {
        demoSubmitting = email;
        router.post(
            demoLogin.url(),
            { email },
            { onFinish: () => (demoSubmitting = null) },
        );
    }
</script>

<AppHead title={t('auth.login_title')} />

<AppAuthLayout title={t('auth.login_title')}>
    {#if status}
        <div class="w-full text-center text-sm font-medium text-green-600">
            {status}
        </div>
    {/if}

    <Form
        {...store.form()}
        resetOnSuccess={['password']}
        class="flex w-full flex-col gap-3"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-1.5">
                <Label for="email" class="text-[13px] text-[#7e7e7e]">
                    {t('auth.university_email')}
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder={t('auth.email_placeholder')}
                    dir="ltr"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-1.5">
                <Label for="password" class="text-[13px] text-[#7e7e7e]">
                    {t('auth.password')}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder={t('auth.password_placeholder')}
                />
                <InputError message={errors.password} />
            </div>

            <div
                class="flex items-center justify-between text-[13px] text-[#7e7e7e]"
            >
                <Label
                    for="remember"
                    class="flex items-center gap-2 text-[#7e7e7e]"
                >
                    <Checkbox
                        id="remember"
                        name="remember"
                        class="size-5 rounded-[2px] border-2 border-black/50"
                    />
                    <span>{t('auth.remember_me')}</span>
                </Label>
                {#if canResetPassword}
                    <Link
                        href={request()}
                        class="text-[13px] text-[#7e7e7e] transition-colors hover:text-brand"
                    >
                        {t('auth.forgot_password')}
                    </Link>
                {/if}
            </div>

            <button
                type="submit"
                disabled={processing}
                data-test="login-button"
                class="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-brand text-sm font-medium text-white transition-colors hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
            >
                {#if processing}<Spinner />{/if}
                {t('auth.login_button')}
            </button>

            {#if canRegister}
                <p class="mt-1 text-center text-[13px] text-[#7e7e7e]">
                    {t('auth.no_account')}
                    <Link
                        href={register()}
                        class="font-bold text-[#7e7e7e] underline decoration-[1.5px] underline-offset-2 hover:text-brand"
                    >
                        {t('auth.create_account')}
                    </Link>
                </p>
            {/if}
        {/snippet}
    </Form>

    {#if demoAccounts.length > 0}
        <div class="w-full">
            <!-- Subtle disclosure so the demo switcher never competes with the real form -->
            <button
                type="button"
                onclick={() => (demoOpen = !demoOpen)}
                aria-expanded={demoOpen}
                class="flex w-full items-center justify-center gap-1.5 rounded-full py-1.5 text-[12px] text-[#9a9a9a] transition-colors hover:text-brand"
            >
                <HugeiconsIcon
                    icon={MagicWand01Icon}
                    strokeWidth={2}
                    class="size-4"
                />
                <span>{t('auth.demo_quick_login')}</span>
            </button>

            {#if demoOpen}
                <div
                    transition:slide={{ duration: 200 }}
                    class="mt-2 flex flex-col gap-1.5 rounded-2xl border border-brand/10 bg-brand/[0.03] p-2"
                >
                    <p
                        class="px-1.5 pb-0.5 text-center text-[11px] text-[#9a9a9a]"
                    >
                        {t('auth.demo_hint')}
                    </p>
                    {#each demoAccounts as account (account.email)}
                        <button
                            type="button"
                            onclick={() => loginAsDemo(account.email)}
                            disabled={demoSubmitting !== null}
                            class="group flex items-center gap-2.5 rounded-xl bg-white/60 px-2.5 py-2 text-start transition-colors hover:bg-white disabled:pointer-events-none disabled:opacity-60"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand/10 text-[13px] font-semibold text-brand"
                            >
                                {account.name.charAt(0)}
                            </span>
                            <span
                                class="flex min-w-0 flex-1 flex-col leading-tight"
                            >
                                <span
                                    class="truncate text-[13px] font-medium text-black"
                                >
                                    {account.name}
                                </span>
                                <span
                                    class="truncate text-[11px] text-[#9a9a9a]"
                                >
                                    {t(`auth.demo_roles.${account.role}`)}
                                </span>
                            </span>
                            {#if demoSubmitting === account.email}
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
            {/if}
        </div>
    {/if}
</AppAuthLayout>
