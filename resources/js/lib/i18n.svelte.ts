import { page } from '@inertiajs/svelte';

type Replacements = Record<string, string | number>;

function resolveTranslation(
    translations: Record<string, unknown>,
    key: string,
): string | undefined {
    const segments = key.split('.');
    let current: unknown = translations;

    for (const segment of segments) {
        if (current === null || typeof current !== 'object') {
            return undefined;
        }

        current = (current as Record<string, unknown>)[segment];
    }

    return typeof current === 'string' ? current : undefined;
}

function applyReplacements(value: string, replacements?: Replacements): string {
    if (!replacements) {
        return value;
    }

    return Object.entries(replacements).reduce(
        (result, [name, replacement]) =>
            result.replaceAll(`:${name}`, String(replacement)),
        value,
    );
}

/**
 * Translate a key from shared Inertia translations (e.g. `nav.home`).
 */
export function t(key: string, replacements?: Replacements): string {
    const translations = page.props.translations as Record<string, unknown>;
    const value = resolveTranslation(translations, key);

    if (value === undefined) {
        return key;
    }

    return applyReplacements(value, replacements);
}

/**
 * Current locale from shared props (`ar` | `en`).
 */
export function currentLocale(): string {
    return (page.props.locale as string) ?? 'ar';
}

/**
 * Format a number using the active locale.
 */
export function formatNumber(
    value: number,
    options?: Intl.NumberFormatOptions,
): string {
    return new Intl.NumberFormat(currentLocale(), options).format(value);
}

/**
 * Format a date using the active locale.
 */
export function formatDate(
    value: string | number | Date,
    options?: Intl.DateTimeFormatOptions,
): string {
    // Transient value consumed immediately by Intl below; never held in reactive state.
    // eslint-disable-next-line svelte/prefer-svelte-reactivity
    const date = value instanceof Date ? value : new Date(value);

    return new Intl.DateTimeFormat(currentLocale(), options).format(date);
}

/**
 * Sync document `lang` and `dir` with Inertia shared props.
 */
export function syncDocumentLocale(): void {
    const locale = currentLocale();
    const direction = (page.props.direction as string) ?? 'rtl';

    document.documentElement.lang = locale;
    document.documentElement.dir = direction;
}
