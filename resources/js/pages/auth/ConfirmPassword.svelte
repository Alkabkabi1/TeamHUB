<script module lang="ts">
    export const layout = () => ({
        title: '',
        description: '',
    });
</script>

<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n.svelte';
    import { store } from '@/routes/password/confirm';

    $effect(() => {
        setLayoutProps({
            title: t('auth.confirm_password_title'),
            description: t('auth.email_university'),
        });
    });
</script>

<AppHead title={t('auth.confirm_password_title')} />

<Form {...store.form()} resetOnSuccess>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">{t('auth.password')}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    class="w-full"
                    disabled={processing}
                    data-test="confirm-password-button"
                >
                    {#if processing}<Spinner />{/if}
                    {t('auth.confirm')}
                </Button>
            </div>
        </div>
    {/snippet}
</Form>
