<script lang="ts">
    let {
        class: className = '',
        fillOpacity = 1,
        ...rest
    }: {
        class?: string;
        fillOpacity?: number | string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 61 60"
    fill="none"
    class={className}
    aria-hidden="true"
    {...rest}
>
    <path
        d="M30.5 0C30.5 0 28.8172 15.4498 22.2622 21.8973C15.7073 28.3447 0 30 0 30C0 30 15.7073 31.6553 22.2622 38.1027C28.8172 44.5502 30.5 60 30.5 60C30.5 60 32.1828 44.5502 38.7378 38.1027C45.2927 31.6553 61 30 61 30C61 30 45.2927 28.3447 38.7378 21.8973C32.1828 15.4498 30.5 0 30.5 0Z"
        fill="currentColor"
        fill-opacity={fillOpacity}
    />
</svg>
