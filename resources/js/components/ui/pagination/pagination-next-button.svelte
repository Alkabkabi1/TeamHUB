<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
	import { Pagination as PaginationPrimitive } from "bits-ui";
	import { cn } from "@/lib/utils.js";
	import { buttonVariants } from "../button/index.js";

	let {
		ref = $bindable(null),
		class: className,
		children,
		...restProps
	}: PaginationPrimitive.NextButtonProps = $props();
</script>

{#snippet Fallback()}
	<span>Next</span>
	<HugeiconsIcon strokeWidth={2} icon={ArrowRight01Icon} class={cn("size-4", className)} />
{/snippet}

<PaginationPrimitive.NextButton
	bind:ref
	aria-label="Go to next page"
	class={cn(buttonVariants({ variant: "ghost" }), "pe-1.5!", className)}
	{...restProps}
>
	{#if children}
		{@render children?.()}
	{:else}
		{@render Fallback()}
	{/if}
</PaginationPrimitive.NextButton>
