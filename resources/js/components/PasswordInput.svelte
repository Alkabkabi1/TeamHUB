<script lang="ts">
    import { ViewIcon, ViewOffIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Input } from '@/components/ui/input';
    import { cn } from '@/lib/utils';

    let { class: className = '', ...rest } = $props();

    let showPassword = $state(false);
</script>

<div class="relative">
    <Input
        type={showPassword ? 'text' : 'password'}
        class={cn('pe-10', className)}
        {...rest}
    />
    <button
        type="button"
        onclick={() => (showPassword = !showPassword)}
        class="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute inset-y-0 end-0 flex items-center rounded-e-md px-3 focus-visible:outline-none focus-visible:ring-[3px]"
        aria-label={showPassword ? 'Hide password' : 'Show password'}
        tabindex={-1}
    >
        {#if showPassword}
            <HugeiconsIcon strokeWidth={2} icon={ViewOffIcon} class="size-4" />
        {:else}
            <HugeiconsIcon strokeWidth={2} icon={ViewIcon} class="size-4" />
        {/if}
    </button>
</div>
