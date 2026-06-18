<script lang="ts">
    import MainSidebarContent from '@/components/MainSidebarContent.svelte';
</script>

<!--
    The <aside> stretches to the full row height (default align-items: stretch)
    so its white background always spans the whole page — no bare strip at the
    bottom. The inner wrapper carries the sticky pinning so the nav stays fixed
    to the viewport while scrolling.
-->
<aside
    class="hidden w-60 shrink-0 bg-white shadow-[8px_8px_48px_0_rgba(0,0,0,0.08)] lg:block"
>
    <div class="sticky top-0 h-[100dvh] overflow-hidden">
        <MainSidebarContent />
    </div>
</aside>
