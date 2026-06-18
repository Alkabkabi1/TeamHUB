<script module lang="ts">
    // Opt out of the default AuthLayout — this page renders its own full-screen design.
    export const layout = () => null;
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import RuwadAuthLayout from '@/layouts/auth/RuwadAuthLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { login } from '@/routes';
    import { email } from '@/routes/password';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title={t('auth.forgot_password_title')} />

<RuwadAuthLayout title={t('auth.forgot_password_title')}>
    {#if status}
        <div class="w-full text-center text-sm font-medium text-green-600">
            {status}
        </div>
    {/if}

    <Form {...email.form()} class="flex w-full flex-col gap-3">
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

            <button
                type="submit"
                disabled={processing}
                data-test="email-password-reset-link-button"
                class="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-brand/50 text-sm font-medium text-brand transition-colors hover:bg-brand/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
            >
                {#if processing}<Spinner />{/if}
                {t('auth.send_reset_link')}
            </button>

            <p class="mt-1 text-center text-[13px] text-[#7e7e7e]">
                {t('auth.have_account')}
                <Link
                    href={login()}
                    class="font-bold text-[#7e7e7e] underline decoration-[1.5px] underline-offset-2 hover:text-brand"
                >
                    {t('auth.sign_in')}
                </Link>
            </p>
        {/snippet}
    </Form>
</RuwadAuthLayout>
