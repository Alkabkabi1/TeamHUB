<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { MinusSignIcon, Tick01Icon } from '@hugeicons/core-free-icons';
	import { Menubar as MenubarPrimitive } from "bits-ui";
	import { cn, type WithoutChildrenOrChild } from "@/lib/utils.js";
	import type { Snippet } from "svelte";
	let {
		ref = $bindable(null),
		class: className,
		checked = $bindable(false),
		indeterminate = $bindable(false),
		inset,
		children: childrenProp,
		...restProps
	}: WithoutChildrenOrChild<MenubarPrimitive.CheckboxItemProps> & {
		inset?: boolean;
		children?: Snippet;
	} = $props();
</script>

<MenubarPrimitive.CheckboxItem
	bind:ref
	bind:checked
	bind:indeterminate
	data-slot="menubar-checkbox-item"
	data-inset={inset}
	class={cn(
		"focus:bg-accent focus:text-accent-foreground focus:**:text-accent-foreground gap-1.5 rounded-md py-1 pe-1.5 ps-7 text-sm data-inset:ps-7 relative flex cursor-default items-center outline-hidden select-none data-disabled:pointer-events-none data-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0",
		className
	)}
	{...restProps}
>
	{#snippet children({ checked: checked, indeterminate: indeterminate })}
		<span
			class="start-1.5 size-4 [&_svg:not([class*='size-'])]:size-4 pointer-events-none absolute flex items-center justify-center"
		>
			{#if indeterminate}
				<HugeiconsIcon strokeWidth={2} icon={MinusSignIcon}  />
			{:else if checked}
				<HugeiconsIcon strokeWidth={2} icon={Tick01Icon}  />
			{/if}
		</span>
		{@render childrenProp?.()}
	{/snippet}
</MenubarPrimitive.CheckboxItem>
