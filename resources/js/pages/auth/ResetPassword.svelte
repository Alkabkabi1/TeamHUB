<script module lang="ts">
    // Opt out of the default AuthLayout — this page renders its own full-screen design.
    export const layout = () => null;
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import RuwadAuthLayout from '@/layouts/auth/RuwadAuthLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { update } from '@/routes/password';

    let {
        token,
        email,
    }: {
        token: string;
        email: string;
    } = $props();
</script>

<AppHead title={t('auth.reset_password_title')} />

<RuwadAuthLayout title={t('auth.reset_password_title')}>
    <Form
        {...update.form()}
        transform={(data) => ({ ...data, token, email })}
        resetOnSuccess={['password', 'password_confirmation']}
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
                    autocomplete="email"
                    value={email}
                    readonly
                    dir="ltr"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-1.5">
                <Label for="password" class="text-[13px] text-[#7e7e7e]">
                    {t('settings.new_password')}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder={t('auth.password_placeholder')}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password_confirmation"
                    class="text-[13px] text-[#7e7e7e]"
                >
                    {t('settings.confirm_password')}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder={t('auth.password_placeholder')}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <button
                type="submit"
                disabled={processing}
                data-test="reset-password-button"
                class="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-brand text-sm font-medium text-white transition-colors hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
            >
                {#if processing}<Spinner />{/if}
                {t('auth.reset_password_button')}
            </button>
        {/snippet}
    </Form>
</RuwadAuthLayout>
