<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { ArrowLeft01Icon } from '@hugeicons/core-free-icons';
	import { Pagination as PaginationPrimitive } from "bits-ui";
	import { cn } from "@/lib/utils.js";
	import { buttonVariants } from "../button/index.js";

	let {
		ref = $bindable(null),
		class: className,
		children,
		...restProps
	}: PaginationPrimitive.PrevButtonProps = $props();
</script>

{#snippet Fallback()}
	<HugeiconsIcon strokeWidth={2} icon={ArrowLeft01Icon} class={cn("size-4", className)} />
	<span>Previous</span>
{/snippet}

<PaginationPrimitive.PrevButton
	bind:ref
	aria-label="Go to previous page"
	class={cn(buttonVariants({ variant: "ghost" }), "ps-1.5!", className)}
	{...restProps}
>
	{#if children}
		{@render children?.()}
	{:else}
		{@render Fallback()}
	{/if}
</PaginationPrimitive.PrevButton>
