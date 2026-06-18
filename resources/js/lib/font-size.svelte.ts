const storageKey = 'accessibility.fontSize';

const fontSizeScales = ['small', 'normal', 'large', 'larger'] as const;

type FontSizeScale = (typeof fontSizeScales)[number];

const scaleValues: Record<FontSizeScale, string> = {
    small: '90%',
    normal: '',
    large: '112.5%',
    larger: '125%',
};

const scaleLabels: Record<FontSizeScale, string> = {
    small: '90%',
    normal: '100%',
    large: '112%',
    larger: '125%',
};

let scale = $state<FontSizeScale>('normal');
let initialized = false;

function isFontSizeScale(value: string | null): value is FontSizeScale {
    return fontSizeScales.includes(value as FontSizeScale);
}

function applyFontSize(next: FontSizeScale): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (next === 'normal') {
        document.documentElement.style.removeProperty('font-size');

        return;
    }

    document.documentElement.style.fontSize = scaleValues[next];
}

function persistFontSize(next: FontSizeScale): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (next === 'normal') {
        localStorage.removeItem(storageKey);

        return;
    }

    localStorage.setItem(storageKey, next);
}

export type FontSizeState = {
    readonly scale: FontSizeScale;
    readonly index: number;
    readonly label: string;
    initialize: () => void;
    setIndex: (index: number) => void;
};

export function fontSizeState(): FontSizeState {
    function initialize(): void {
        if (initialized || typeof window === 'undefined') {
            return;
        }

        initialized = true;

        const savedScale = localStorage.getItem(storageKey);
        scale = isFontSizeScale(savedScale) ? savedScale : 'normal';

        if (savedScale !== null && !isFontSizeScale(savedScale)) {
            localStorage.removeItem(storageKey);
        }

        applyFontSize(scale);
    }

    function setIndex(index: number): void {
        const nextIndex = Math.min(
            Math.max(index, 0),
            fontSizeScales.length - 1,
        );

        scale = fontSizeScales[nextIndex] ?? 'normal';
        applyFontSize(scale);
        persistFontSize(scale);
    }

    return {
        get scale() {
            return scale;
        },
        get index() {
            return fontSizeScales.indexOf(scale);
        },
        get label() {
            return scaleLabels[scale];
        },
        initialize,
        setIndex,
    };
}
