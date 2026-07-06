<script lang="ts">
    /**
     * Full-width site footer. Rendered once by GuestLayout so it appears on every
     * page (matches the Figma footer that spans the full width below the sidebar).
     */
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';
</script>

<footer class="bg-black/5 py-8 text-center text-sm text-black sm:text-base">
    <p>
        {t('support.footer')}
        <Link href="#" class="cursor-pointer transition-colors hover:text-brand"
            >LinkedIn</Link
        >
        |
        <Link href="#" class="cursor-pointer transition-colors hover:text-brand"
            >X</Link
        >
    </p>
</footer>
