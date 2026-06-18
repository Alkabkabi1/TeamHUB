<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { MinusSignIcon, Tick01Icon } from '@hugeicons/core-free-icons';
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";
	import { cn, type WithoutChildrenOrChild } from "@/lib/utils.js";
	import type { Snippet } from "svelte";

	let {
		ref = $bindable(null),
		checked = $bindable(false),
		indeterminate = $bindable(false),
		class: className,
		children: childrenProp,
		...restProps
	}: WithoutChildrenOrChild<DropdownMenuPrimitive.CheckboxItemProps> & {
		children?: Snippet;
	} = $props();
</script>

<DropdownMenuPrimitive.CheckboxItem
	bind:ref
	bind:checked
	bind:indeterminate
	data-slot="dropdown-menu-checkbox-item"
	class={cn(
		"focus:bg-accent focus:text-accent-foreground focus:**:text-accent-foreground gap-1.5 rounded-md py-1 pe-8 ps-1.5 text-sm data-inset:ps-7 [&_svg:not([class*='size-'])]:size-4 relative flex cursor-default items-center outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0",
		className
	)}
	{...restProps}
>
	{#snippet children({ checked, indeterminate })}
		<span
			class="absolute end-2 flex items-center justify-center pointer-events-none"
			data-slot="dropdown-menu-checkbox-item-indicator"
		>
			{#if indeterminate}
				<HugeiconsIcon strokeWidth={2} icon={MinusSignIcon}  />
			{:else if checked}
				<HugeiconsIcon strokeWidth={2} icon={Tick01Icon}  />
			{/if}
		</span>
		{@render childrenProp?.()}
	{/snippet}
</DropdownMenuPrimitive.CheckboxItem>
