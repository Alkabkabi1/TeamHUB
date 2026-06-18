import { router } from '@inertiajs/svelte';
import { currentLocale } from '@/lib/i18n.svelte';

const storageKey = 'accessibility.screenReader';

let enabled = $state(false);
let initialized = false;
let removeClickListener: (() => void) | null = null;
let removeNavigateListener: (() => void) | null = null;

function speechLanguage(): string {
    return currentLocale() === 'ar' ? 'ar-SA' : 'en-US';
}

function canUseSpeech(): boolean {
    return typeof window !== 'undefined' && 'speechSynthesis' in window;
}

function cleanText(value: string | null | undefined): string {
    return (value ?? '').replace(/\s+/g, ' ').trim();
}

function speak(text: string): void {
    const message = cleanText(text);

    if (!enabled || !message || !canUseSpeech()) {
        return;
    }

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(message);
    utterance.lang = speechLanguage();
    utterance.rate = 0.95;

    window.speechSynthesis.speak(utterance);
}

function pageAnnouncement(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const heading = cleanText(document.querySelector('h1')?.textContent);
    const title = cleanText(document.title);

    return heading || title;
}

function announceCurrentPage(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.setTimeout(() => speak(pageAnnouncement()), 120);
}

function textFromElement(element: Element): string {
    const labelledBy = element.getAttribute('aria-labelledby');

    if (labelledBy) {
        const label = labelledBy
            .split(/\s+/)
            .map((id) => document.getElementById(id)?.textContent)
            .filter(Boolean)
            .join(' ');

        if (cleanText(label)) {
            return cleanText(label);
        }
    }

    return (
        cleanText(element.getAttribute('aria-label')) ||
        cleanText(element.getAttribute('title')) ||
        cleanText(element.textContent) ||
        cleanText(
            element instanceof HTMLInputElement ||
                element instanceof HTMLTextAreaElement
                ? element.placeholder || element.value
                : '',
        )
    );
}

function handleClick(event: MouseEvent): void {
    if (!enabled) {
        return;
    }

    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const interactiveElement = target.closest(
        'button, a, input, select, textarea, [role="button"], [aria-label], [aria-labelledby]',
    );

    if (!interactiveElement) {
        return;
    }

    speak(textFromElement(interactiveElement));
}

function attachListeners(): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (!removeClickListener) {
        document.addEventListener('click', handleClick, true);
        removeClickListener = () =>
            document.removeEventListener('click', handleClick, true);
    }

    if (!removeNavigateListener) {
        removeNavigateListener = router.on('navigate', announceCurrentPage);
    }
}

function detachListeners(): void {
    removeClickListener?.();
    removeClickListener = null;

    removeNavigateListener?.();
    removeNavigateListener = null;

    if (canUseSpeech()) {
        window.speechSynthesis.cancel();
    }
}

function persistEnabled(value: boolean): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(storageKey, value ? 'true' : 'false');
}

export type ScreenReaderState = {
    readonly enabled: boolean;
    initialize: () => void;
    toggle: () => void;
};

export function screenReaderState(): ScreenReaderState {
    function initialize(): void {
        if (initialized || typeof window === 'undefined') {
            return;
        }

        initialized = true;
        enabled = localStorage.getItem(storageKey) === 'true';

        if (enabled) {
            attachListeners();
        }
    }

    function toggle(): void {
        enabled = !enabled;
        persistEnabled(enabled);

        if (enabled) {
            attachListeners();
            speak(
                currentLocale() === 'ar'
                    ? 'تم تشغيل قارئ الشاشة'
                    : 'Screen reader enabled',
            );
            announceCurrentPage();

            return;
        }

        detachListeners();
    }

    return {
        get enabled() {
            return enabled;
        },
        initialize,
        toggle,
    };
}
