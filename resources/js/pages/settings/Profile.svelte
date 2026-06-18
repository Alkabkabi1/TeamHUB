<script module lang="ts">
    import { t as tLayout } from '@/lib/i18n.svelte';
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: tLayout('settings.profile_title'),
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { t } from '@/lib/i18n.svelte';
    import { send } from '@/routes/verification';

    let {
        mustVerifyEmail,
        status = '',
    }: {
        mustVerifyEmail: boolean;
        status?: string;
    } = $props();

    const user = $derived(page.props.auth.user);
</script>

<AppHead title={t('settings.profile_title')} />

<h1 class="sr-only">{t('settings.profile_title')}</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title={t('settings.profile_title')}
        description={t('settings.profile_updated')}
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">{t('settings.full_name')}</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder={t('settings.full_name')}
                />
                <InputError class="mt-2" message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">{t('settings.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder={t('settings.email')}
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            {#if mustVerifyEmail && !user.email_verified_at}
                <div>
                    <p class="-mt-4 text-sm text-muted-foreground">
                        {t('auth.verify_email_title')}
                        <TextLink href={send()} as="button">
                            {t('auth.send_reset_link')}
                        </TextLink>
                    </p>

                    {#if status === 'verification-link-sent'}
                        <div class="mt-2 text-sm font-medium text-green-600">
                            {status}
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button">{t('app.save')}</Button
                >
            </div>
        {/snippet}
    </Form>
</div>

<DeleteUser />
