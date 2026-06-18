<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.4"
    stroke-linecap="round"
    stroke-linejoin="round"
    class={className}
    {...rest}
>
    <path d="M4 7h16" />
    <path d="M4 12h10" />
    <path d="M4 17h16" />
</svg>
