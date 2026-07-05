import { page, router } from '@inertiajs/svelte';

/**
 * Fallback brand color, kept in sync with config/theme.php and app.css. Only
 * used if the shared `theme` prop is ever missing.
 */
const DEFAULT_BRAND = '#c8924a';

/**
 * Apply a brand color by overriding the `--brand` custom property on the root
 * element. All brand shades derive from it in CSS, so this re-themes the whole
 * UI. Passing a falsy value restores the default.
 */
export function applyBrandColor(brand?: string | null): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.style.setProperty(
        '--brand',
        brand || DEFAULT_BRAND,
    );
}

/**
 * Keep the brand color in sync with the shared `theme.brand` Inertia prop.
 * Applies on the initial load and on every client-side navigation, so club
 * pages (which override the prop) re-theme automatically and revert on leave.
 */
export function initializeBrandTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const apply = (): void => {
        const theme = page.props?.theme as { brand?: string } | undefined;
        applyBrandColor(theme?.brand);
    };

    apply();
    router.on('navigate', apply);
}
