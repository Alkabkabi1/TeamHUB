<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { Tick01Icon } from '@hugeicons/core-free-icons';
	import { ContextMenu as ContextMenuPrimitive } from "bits-ui";
	import { cn, type WithoutChild } from "@/lib/utils.js";
	let {
		ref = $bindable(null),
		class: className,
		inset,
		children: childrenProp,
		...restProps
	}: WithoutChild<ContextMenuPrimitive.RadioItemProps> & {
		inset?: boolean;
	} = $props();
</script>

<ContextMenuPrimitive.RadioItem
	bind:ref
	data-slot="context-menu-radio-item"
	data-inset={inset}
	class={cn(
		"focus:bg-accent focus:text-accent-foreground gap-1.5 rounded-md py-1 pe-8 ps-1.5 text-sm data-inset:ps-7 [&_svg:not([class*='size-'])]:size-4 relative flex cursor-default items-center outline-hidden select-none data-disabled:pointer-events-none data-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0",
		className
	)}
	{...restProps}
>
	{#snippet children({ checked })}
		<span class="absolute end-2 pointer-events-none">
			{#if checked}
				<HugeiconsIcon strokeWidth={2} icon={Tick01Icon}  />
			{/if}
		</span>
		{@render childrenProp?.({ checked })}
	{/snippet}
</ContextMenuPrimitive.RadioItem>
