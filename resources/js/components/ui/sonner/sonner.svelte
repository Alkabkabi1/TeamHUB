<script lang="ts">
	// @ts-nocheck
	import { HugeiconsIcon } from '@hugeicons/svelte';
	import { Alert01Icon, CheckmarkCircle01Icon, InformationCircleIcon, OctagonXIcon } from '@hugeicons/core-free-icons';
	import { Toaster as Sonner, type ToasterProps as SonnerProps } from "svelte-sonner";
	import { mode } from "mode-watcher";
	import { Spinner } from "@/components/ui/spinner";
	let { ...restProps }: SonnerProps = $props();
</script>

<Sonner
	theme={mode.current}
	class="toaster group"
	style="--normal-bg: var(--color-popover); --normal-text: var(--color-popover-foreground); --normal-border: var(--color-border);"
	{...restProps}
>
	{#snippet loadingIcon()}
		<Spinner class="size-4" />
	{/snippet}
	{#snippet successIcon()}
		<HugeiconsIcon strokeWidth={2} icon={CheckmarkCircle01Icon} class="size-4" />
	{/snippet}
	{#snippet errorIcon()}
		<HugeiconsIcon strokeWidth={2} icon={OctagonXIcon} class="size-4" />
	{/snippet}
	{#snippet infoIcon()}
		<HugeiconsIcon strokeWidth={2} icon={InformationCircleIcon} class="size-4" />
	{/snippet}
	{#snippet warningIcon()}
		<HugeiconsIcon strokeWidth={2} icon={Alert01Icon} class="size-4" />
	{/snippet}
</Sonner>
