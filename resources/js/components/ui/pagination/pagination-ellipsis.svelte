<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { MoreHorizontalIcon } from '@hugeicons/core-free-icons';
	import type { HTMLAttributes } from "svelte/elements";
	import { cn, type WithElementRef, type WithoutChildren } from "@/lib/utils.js";
	let {
		ref = $bindable(null),
		class: className,
		...restProps
	}: WithoutChildren<WithElementRef<HTMLAttributes<HTMLSpanElement>>> = $props();
</script>

<span
	bind:this={ref}
	aria-hidden="true"
	data-slot="pagination-ellipsis"
	class={cn("size-8 items-center justify-center [&_svg:not([class*='size-'])]:size-4 flex items-center justify-center", className)}
	{...restProps}
>
	<HugeiconsIcon strokeWidth={2} icon={MoreHorizontalIcon}  />
	<span class="sr-only">More pages</span>
</span>
