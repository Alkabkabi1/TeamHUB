# Phase 7 Polish Notes

The following issues were intentionally deferred while implementing Phase 5:

## Dark mode persistence
- The main app currently forces light mode in `resources/js/lib/theme.svelte.ts`.
- `resources/js/app.ts` initializes that forced-light behavior on every load.
- `tests/Feature/ThemeBrandTest.php` currently verifies the forced-light behavior and will need updating when app-wide dark mode is restored.

## Brand/theme reset on some task pages
- `app/Http/Controllers/TaskController.php` does not currently pass workspace/project theme overrides the way other committee pages do.
- This can cause brand styling to fall back when navigating between project pages and task pages.

## Remaining old-project traces
- Review remaining legacy copy and appearance drift across the member dashboard, shell navigation, and preview-only TeamHUB UI.
- Pay special attention to preview-only theme toggles versus the main app theme system so the product behaves consistently.
