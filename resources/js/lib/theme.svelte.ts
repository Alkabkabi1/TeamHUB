import type { ResolvedAppearance } from '@/types';

export type { ResolvedAppearance };

export type ThemeState = {
    resolvedAppearance: () => ResolvedAppearance;
};

/**
 * The application is light-only. There is no dark or system mode, so the
 * resolved appearance is always `light`.
 */
const applyLightTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

/**
 * Pin the document to light mode and clear any appearance preference that may
 * have been persisted by older builds that supported dark/system modes.
 */
export function initializeTheme(): void {
    if (typeof window !== 'undefined') {
        localStorage.removeItem('appearance');
        document.cookie =
            'appearance=light;path=/;max-age=31536000;SameSite=Lax';
    }

    applyLightTheme();
}

export function themeState(): ThemeState {
    return {
        resolvedAppearance: () => 'light',
    };
}
