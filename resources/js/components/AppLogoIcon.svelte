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
    viewBox="0 0 40 40"
    fill="none"
    class={className}
    aria-hidden="true"
    {...rest}
>
    <rect x="2" y="2" width="36" height="36" rx="10" fill="#0F172A" />
    <path d="M20 7 30 12.5 20 18 10 12.5 20 7Z" fill="#E0B16B" />
    <path d="M10 12.5 20 18V29L10 23.5V12.5Z" fill="#C8924A" />
    <path d="M30 12.5 20 18V29L30 23.5V12.5Z" fill="#B98335" />
    <path
        d="M20 12.2 25.8 15.3 20 18.4 14.2 15.3 20 12.2Z"
        fill="#FFF3DF"
        fill-opacity="0.92"
    />
</svg>
