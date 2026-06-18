<script lang="ts">
	import { cn } from "@/lib/utils.js";
	import type { HTMLAttributes } from "svelte/elements";

	let {
		class: className,
		role = "status",
		"aria-label": ariaLabel = "Loading",
		...restProps
	}: HTMLAttributes<HTMLSpanElement> = $props();
</script>

<!--
	A circular ring spinner (a spinning arc), not a rotating glyph. Inherits the
	current text color via `border-current`; pass `class` to size/recolor it.
-->
<span
	{role}
	aria-label={ariaLabel}
	class={cn(
		"inline-block size-4 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent",
		className,
	)}
	{...restProps}
></span>
