/**
 * Frontend UI configuration toggles.
 *
 * These are plain build-time flags — flip a value here to enable/disable the
 * corresponding behavior across the whole app (no backend change needed).
 */
export const uiConfig = {
    /**
     * When true, the main app logo (`AppLogoIcon`) switches to an
     * English-specific mark while the app is on the `en` locale. Set to
     * `false` to always use the default (Arabic) logo regardless of locale.
     */
    localizedLogo: false,
} as const;
