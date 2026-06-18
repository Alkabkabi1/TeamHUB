<script lang="ts">
    import { AlertCircleIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

    let {
        errors = [],
        title = 'Something went wrong.',
    }: {
        errors: string[];
        title?: string;
    } = $props();

    const uniqueErrors = $derived([...new Set(errors)]);
</script>

<Alert variant="destructive">
    <HugeiconsIcon strokeWidth={2} icon={AlertCircleIcon} class="size-4" />
    <AlertTitle>{title}</AlertTitle>
    <AlertDescription>
        <ul class="list-inside list-disc text-sm">
            {#each uniqueErrors as error (error)}
                <li>{error}</li>
            {/each}
        </ul>
    </AlertDescription>
</Alert>
