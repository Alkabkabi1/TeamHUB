<script lang="ts">
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
	import type { ComponentProps } from "svelte";
	import { cn } from "@/lib/utils.js";
	import { PaginationLink } from "./index.js";
	type PaginationNextProps = ComponentProps<typeof PaginationLink>;

	let { class: className, ...restProps }: PaginationNextProps = $props();
</script>

<PaginationLink
	aria-label="Go to next page"
	size="default"
	class={cn("pe-1.5!", className)}
	{...restProps}
>
	<span class="cn-pagination-next-text hidden sm:block">Next</span>
	<HugeiconsIcon strokeWidth={2} icon={ArrowRight01Icon} data-icon="inline-end" />
</PaginationLink>
