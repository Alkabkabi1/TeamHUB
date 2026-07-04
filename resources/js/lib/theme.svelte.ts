import type { ResolvedAppearance } from '@/types';

export type { ResolvedAppearance };

export type ThemeState = {
    appearance: () => ResolvedAppearance;
    resolvedAppearance: () => ResolvedAppearance;
    initialize: () => void;
    setAppearance: (next: ResolvedAppearance) => void;
    toggleAppearance: () => void;
};

const storageKey = 'appearance';
const defaultAppearance: ResolvedAppearance = 'light';

let appearance = $state<ResolvedAppearance>(defaultAppearance);
let initialized = false;

function isResolvedAppearance(
    value: string | null,
): value is ResolvedAppearance {
    return value === 'light' || value === 'dark';
}

function readAppearanceCookie(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/(?:^|; )appearance=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
}

function applyTheme(next: ResolvedAppearance): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('dark', next === 'dark');
    document.documentElement.style.colorScheme = next;
}

function persistAppearance(next: ResolvedAppearance): void {
    if (typeof window !== 'undefined') {
        localStorage.setItem(storageKey, next);
        document.cookie = `appearance=${encodeURIComponent(next)};path=/;max-age=31536000;SameSite=Lax`;
    }
}

export function initializeTheme(): void {
    if (initialized) {
        applyTheme(appearance);

        return;
    }

    initialized = true;

    if (typeof window !== 'undefined') {
        const savedAppearance =
            localStorage.getItem(storageKey) ?? readAppearanceCookie();

        if (isResolvedAppearance(savedAppearance)) {
            appearance = savedAppearance;
        } else if (savedAppearance !== null) {
            localStorage.removeItem(storageKey);
            document.cookie =
                'appearance=light;path=/;max-age=31536000;SameSite=Lax';
            appearance = defaultAppearance;
        }
    }

    applyTheme(appearance);
}

export function themeState(): ThemeState {
    function setAppearance(next: ResolvedAppearance): void {
        appearance = next;
        applyTheme(next);
        persistAppearance(next);
    }

    return {
        appearance: () => appearance,
        resolvedAppearance: () => appearance,
        initialize: initializeTheme,
        setAppearance,
        toggleAppearance: () =>
            setAppearance(appearance === 'dark' ? 'light' : 'dark'),
    };
}
