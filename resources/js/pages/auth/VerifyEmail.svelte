<script module lang="ts">
    export const layout = () => ({
        title: '',
        description: '',
    });
</script>

<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n.svelte';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();

    $effect(() => {
        setLayoutProps({
            title: t('auth.verify_email_title'),
            description: t('auth.email_university'),
        });
    });
</script>

<AppHead title={t('auth.verify_email_title')} />

{#if status === 'verification-link-sent'}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        {status}
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            {t('auth.send_reset_link')}
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            {t('nav.logout')}
        </TextLink>
    {/snippet}
</Form>
