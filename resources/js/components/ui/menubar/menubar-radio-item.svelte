<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { Tick01Icon } from '@hugeicons/core-free-icons';
	import { Menubar as MenubarPrimitive } from "bits-ui";
	import { cn, type WithoutChild } from "@/lib/utils.js";
	let {
		ref = $bindable(null),
		class: className,
		inset,
		children: childrenProp,
		...restProps
	}: WithoutChild<MenubarPrimitive.RadioItemProps> & {
		inset?: boolean;
	} = $props();
</script>

<MenubarPrimitive.RadioItem
	bind:ref
	data-slot="menubar-radio-item"
	data-inset={inset}
	class={cn(
		"focus:bg-accent focus:text-accent-foreground focus:**:text-accent-foreground gap-1.5 rounded-md py-1 pe-1.5 ps-7 text-sm data-disabled:opacity-50 data-inset:ps-7 [&_svg:not([class*='size-'])]:size-4 relative flex cursor-default items-center outline-hidden select-none data-disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0",
		className
	)}
	{...restProps}
>
	{#snippet children({ checked })}
		<span
			class="start-1.5 size-4 [&_svg:not([class*='size-'])]:size-4 pointer-events-none absolute flex items-center justify-center"
		>
			{#if checked}
				<HugeiconsIcon strokeWidth={2} icon={Tick01Icon}  />
			{/if}
		</span>
		{@render childrenProp?.({ checked })}
	{/snippet}
</MenubarPrimitive.RadioItem>
