<script module lang="ts">
    import { SecurityCheckIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { t as tLayout } from '@/lib/i18n.svelte';
    import { edit } from '@/routes/security';

    export const layout = {
        breadcrumbs: [
            {
                title: tLayout('settings.security_title'),
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { onDestroy } from 'svelte';
    import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { t } from '@/lib/i18n.svelte';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { disable, enable } from '@/routes/two-factor';

    let {
        canManageTwoFactor = false,
        requiresConfirmation = false,
        twoFactorEnabled = false,
    }: {
        canManageTwoFactor?: boolean;
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
    } = $props();

    const twoFactorAuth = twoFactorAuthState();
    let showSetupModal = $state(false);

    onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

<AppHead title={t('settings.security_title')} />

<h1 class="sr-only">{t('settings.security_title')}</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title={t('settings.security_title')}
        description={t('settings.password_updated')}
    />

    <Form
        {...SecurityController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
        resetOnSuccess
        resetOnError={['password', 'password_confirmation', 'current_password']}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="current_password"
                    >{t('settings.current_password')}</Label
                >
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder={t('settings.current_password')}
                />
                <InputError message={errors.current_password} />
            </div>

            <div class="grid gap-2">
                <Label for="password">{t('settings.new_password')}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder={t('settings.new_password')}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"
                    >{t('settings.confirm_password')}</Label
                >
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder={t('settings.confirm_password')}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-password-button"
                >
                    {t('settings.save_password')}
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

{#if canManageTwoFactor}
    <div class="space-y-6">
        <Heading
            variant="small"
            title={t('settings.two_factor')}
            description={t('settings.appearance_description')}
        />

        {#if !twoFactorEnabled}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    {t('settings.two_factor')}
                </p>

                <div>
                    {#if twoFactorAuth.hasSetupData()}
                        <Button onclick={() => (showSetupModal = true)}>
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={SecurityCheckIcon}
                                class="size-4"
                            />{t('auth.confirm')}
                        </Button>
                    {:else}
                        <Form
                            {...enable.form()}
                            onSuccess={() => (showSetupModal = true)}
                        >
                            {#snippet children({ processing })}
                                <Button type="submit" disabled={processing}>
                                    {t('settings.enable_2fa')}
                                </Button>
                            {/snippet}
                        </Form>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    {t('settings.two_factor')}
                </p>

                <div class="relative inline">
                    <Form {...disable.form()}>
                        {#snippet children({ processing })}
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                {t('settings.disable_2fa')}
                            </Button>
                        {/snippet}
                    </Form>
                </div>

                <TwoFactorRecoveryCodes />
            </div>
        {/if}

        <TwoFactorSetupModal
            bind:isOpen={showSetupModal}
            {requiresConfirmation}
            {twoFactorEnabled}
        />
    </div>
{/if}
