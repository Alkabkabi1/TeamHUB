# Phase 7 — Polish, i18n & Tests

**Status:** Complete — see [PHASE_7_CLOSURE.md](./PHASE_7_CLOSURE.md)  
**Branch:** `release/v1.0-rc1`  
**Completed:** 2026-07-08

---

## Exit criteria

- [x] `composer ci:check` passes (339 tests)
- [x] Policy tests for all three policies
- [x] User guide uses Workspace / Project / Task vocabulary
- [x] RTL task surfaces covered by `TaskRtlLayoutTest`
- [x] Mobile task UI hardening (full-width actions, scrollable filters, `dir="ltr"` datetimes)
- [x] Octane smoke tests (`OctaneSmokeTest`)
- [x] `docs/PHASE_7_CLOSURE.md` written

---

## Summary

| Area | Status |
|------|--------|
| Arabic / RTL | Done — automated tests + CSS logical properties |
| UX polish | Done |
| Testing | Done — 339 tests |
| Documentation | Done |
| Performance | Done — indexes + eager loads (no comment count needed) |
| Octane | Done — smoke tests |

**Next:** Phase 8 — Deploy & pilot
