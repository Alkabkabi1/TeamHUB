<script lang="ts">
	import type { HTMLInputAttributes, HTMLInputTypeAttribute } from "svelte/elements";
	import { cn, type WithElementRef } from "@/lib/utils.js";

	type InputType = Exclude<HTMLInputTypeAttribute, "file">;

	type Props = WithElementRef<
		Omit<HTMLInputAttributes, "type"> &
			({ type: "file"; files?: FileList } | { type?: InputType; files?: undefined })
	>;

	let {
		ref = $bindable(null),
		value = $bindable(),
		type,
		files = $bindable(),
		class: className,
		"data-slot": dataSlot = "input",
		...restProps
	}: Props = $props();

	// Single source of truth for input styling across the whole project.
	// The focus state is a thin (2px) soft brand ring — override per-instance
	// via `class`, where tailwind-merge keeps the last conflicting value.
	const inputClass =
		"flex h-10 w-full min-w-0 rounded-[10px] border border-black/20 bg-white px-5 text-start text-xs text-black outline-none transition-[color,box-shadow] placeholder:text-black/30 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30 file:inline-flex file:h-6 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground";
</script>

{#if type === "file"}
	<input
		bind:this={ref}
		data-slot={dataSlot}
		class={cn(inputClass, className)}
		type="file"
		bind:files
		bind:value
		{...restProps}
	/>
{:else}
	<input
		bind:this={ref}
		data-slot={dataSlot}
		class={cn(inputClass, className)}
		{type}
		bind:value
		{...restProps}
	/>
{/if}
