<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { ArrowDown01Icon } from '@hugeicons/core-free-icons';
	import { Select as SelectPrimitive } from "bits-ui";
	import { cn, type WithoutChildrenOrChild } from "@/lib/utils.js";
	let {
		ref = $bindable(null),
		class: className,
		...restProps
	}: WithoutChildrenOrChild<SelectPrimitive.ScrollDownButtonProps> = $props();
</script>

<SelectPrimitive.ScrollDownButton
	bind:ref
	data-slot="select-scroll-down-button"
	class={cn("bg-popover z-10 flex cursor-default items-center justify-center py-1 [&_svg:not([class*='size-'])]:size-4 bottom-0 w-full", className)}
	{...restProps}
>
	<HugeiconsIcon strokeWidth={2} icon={ArrowDown01Icon}  />
</SelectPrimitive.ScrollDownButton>
