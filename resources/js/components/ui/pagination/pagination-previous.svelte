<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { ArrowLeft01Icon } from '@hugeicons/core-free-icons';
	import type { ComponentProps } from "svelte";
	import { cn } from "@/lib/utils.js";
	import { PaginationLink } from "./index.js";
	type PaginationPreviousProps = ComponentProps<typeof PaginationLink>;

	let { class: className, ...restProps }: PaginationPreviousProps = $props();
</script>

<PaginationLink
	aria-label="Go to previous page"
	size="default"
	class={cn("ps-1.5!", className)}
	{...restProps}
>
	<HugeiconsIcon strokeWidth={2} icon={ArrowLeft01Icon} data-icon="inline-start" />
	<span class="cn-pagination-previous-text hidden sm:block">Previous</span>
</PaginationLink>
