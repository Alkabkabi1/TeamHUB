<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { MinusSignIcon, Tick01Icon } from '@hugeicons/core-free-icons';
	import { Checkbox as CheckboxPrimitive } from "bits-ui";
	import { cn, type WithoutChildrenOrChild } from "@/lib/utils.js";
	let {
		ref = $bindable(null),
		checked = $bindable(false),
		indeterminate = $bindable(false),
		class: className,
		...restProps
	}: WithoutChildrenOrChild<CheckboxPrimitive.RootProps> = $props();
</script>

<CheckboxPrimitive.Root
	bind:ref
	data-slot="checkbox"
	class={cn(
		"peer relative flex size-4 shrink-0 items-center justify-center rounded-[4px] border border-black/30 bg-white text-white outline-none transition-colors data-[state=checked]:border-brand data-[state=checked]:bg-brand data-[state=checked]:text-white data-[state=indeterminate]:border-brand data-[state=indeterminate]:bg-brand data-[state=indeterminate]:text-white focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20 group-has-disabled/field:opacity-50 disabled:cursor-not-allowed disabled:opacity-50 after:absolute after:-inset-x-3 after:-inset-y-2 dark:bg-input/30",
		className
	)}
	bind:checked
	bind:indeterminate
	{...restProps}
>
	{#snippet children({ checked, indeterminate })}
		<div
			data-slot="checkbox-indicator"
			class="[&>svg]:size-3.5 grid place-content-center text-current transition-none"
		>
			{#if checked}
				<HugeiconsIcon strokeWidth={2} icon={Tick01Icon}  />
			{:else if indeterminate}
				<HugeiconsIcon strokeWidth={2} icon={MinusSignIcon}  />
			{/if}
		</div>
	{/snippet}
</CheckboxPrimitive.Root>
