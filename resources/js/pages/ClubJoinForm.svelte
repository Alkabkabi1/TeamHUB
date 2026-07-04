<script module lang="ts">
    // Opt out of the default AuthLayout — this page renders its own full-screen design.
    export const layout = () => null;
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import TeamHubAuthLayout from '@/layouts/auth/TeamHubAuthLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { show } from '@/routes/clubs';
    import { store } from '@/routes/clubs/join';
    import type { ClubRef } from '@/types';

    type Defaults = {
        full_name: string;
        university_email: string;
    };

    let {
        club,
        defaults = { full_name: '', university_email: '' },
    }: {
        club: ClubRef;
        defaults?: Defaults;
    } = $props();

    type Field = {
        name: string;
        type?: 'email' | 'number';
        value?: string;
        min?: number;
        max?: number;
    };

    const fields: Field[] = [
        { name: 'full_name', value: defaults.full_name },
        {
            name: 'university_email',
            type: 'email',
            value: defaults.university_email,
        },
        { name: 'phone' },
        { name: 'level' },
        { name: 'major' },
        { name: 'skills' },
        { name: 'weekly_hours', type: 'number', min: 1, max: 40 },
        { name: 'tools' },
        { name: 'motivation' },
        { name: 'contribution' },
    ];
</script>

<AppHead title="{t('join.title')} — {club.name}" />

<TeamHubAuthLayout
    title="{t('join.title')} — {club.name}"
    backHref={show(club.id).url}
    wide
>
    <Form {...store.form(club.id)} class="flex w-full flex-col">
        {#snippet children({ errors, processing })}
            <InputError message={errors.club} class="mb-4 text-center" />

            <div class="grid grid-cols-1 gap-x-10 gap-y-5 md:grid-cols-2">
                {#each fields as field (field.name)}
                    <div class="space-y-1.5">
                        <Label for={field.name} class="text-sm text-[#7e7e7e]">
                            {t(`join.${field.name}`)}
                        </Label>
                        <Input
                            id={field.name}
                            name={field.name}
                            type={field.type}
                            value={field.value}
                            min={field.min}
                            max={field.max}
                            placeholder={t(`join.placeholder.${field.name}`)}
                        />
                        <InputError message={errors[field.name]} />
                    </div>
                {/each}
            </div>

            <button
                type="submit"
                disabled={processing}
                class="mx-auto mt-8 flex h-10 w-full max-w-[500px] items-center justify-center gap-2 rounded-full bg-brand text-sm font-medium text-white transition-colors hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
            >
                {#if processing}<Spinner />{/if}
                {t('join.submit')}
            </button>
        {/snippet}
    </Form>
</TeamHubAuthLayout>
