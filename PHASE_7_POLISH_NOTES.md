# Phase 7 Polish Notes

Issues deferred from earlier phases. Updated at Phase 7 kickoff (2026-07-07).

## Resolved

### Dark mode persistence
- **Status:** Done. `resources/js/lib/theme.svelte.ts` persists appearance in `localStorage` and a cookie; no longer forces light mode on load.
- `ThemeBrandTest` verifies client-side appearance bootstrap.

### Brand/theme on task pages
- **Status:** Done. `TaskController` passes `theme`, `workspace.theme`, and `project.theme` on index and show.
- Covered by `ThemeBrandTest` (project theme, workspace fallback).

## Still to review

### Remaining old-project traces
- Review legacy copy and appearance drift across the member dashboard, shell navigation, and preview-only TeamHUB UI.
- Pay special attention to preview-only theme toggles versus the main app theme system.
- As of Phase 7 start: user-facing strings use Workspace / Project / Task; remaining `club` / `committee` references are in component docblocks and migration history only.
