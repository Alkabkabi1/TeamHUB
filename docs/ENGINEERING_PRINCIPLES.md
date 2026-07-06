# TeamHUB — Engineering Principles

**Status:** Phase 3.5 complete — guidance applied through domain, UI, and runtime migration.  
**Applies to:** All contributors and AI-assisted implementation (Cursor)

---

## Purpose

This document locks engineering decisions **before** code changes begin. It prevents drift back toward the original university-club codebase (Ruwad) during re-engineering.

---

## Non-negotiable rule

> **Every remaining feature must answer:** *"Would this still make sense in a generic company, startup, open-source team, or community organization?"*
>
> - **No** → remove it  
> - **Yes** → generalize it  
> - **Never** keep a feature only because it existed in the university project  

When implementing, if a task would preserve legacy behavior “for compatibility,” stop and ask whether it passes this rule.

---

## Standalone product commitments

| Commitment | Meaning for implementers |
|------------|-------------------------|
| **Standalone product** | User-facing copy, routes, and models reflect TeamHUB — not “clubs” or “committees” |
| **No university compatibility** | No feature flags for academic mode; no dual code paths |
| **Breaking changes allowed** | Prefer correct domain over stable URLs or class names |
| **Reuse only when it fits** | Do not wrap legacy controllers “temporarily” unless Phase plan explicitly allows a one-phase bridge |
| **Remove university concepts** | Delete or redesign — do not hide behind nav or env vars |

---

## What not to do

| Anti-pattern | Why |
|--------------|-----|
| Rename in UI only, keep `Club` model | Perpetuates dual mental model |
| `TeamHubAppLayout` wrapping old pages | Two systems; use native pages in one `AppShell` |
| Feature flag `TEAMHUB_UNIVERSITY_MODE` | Violates “no university compatibility” |
| Keeping Event/Certificate models “for later” | Dead weight; delete in Phase 1 |
| Redirect chains `/clubs` → `/hub` → `/projects` | Legacy debt; one canonical URL per resource |

---

## Re-engineering phases (reference)

| Phase | Focus |
|-------|--------|
| **0** | Product definition — **this doc + PRODUCT_VISION + DOMAIN_MODEL** (no code) |
| **1** | Domain: migrations, models, policies, routes |
| **2** | UI: single AppShell, native pages |
| **3** | Workflow: task statuses, deliverables, notifications |
| **4** | Services: Workspace, Project, Task, Deliverable, Notification |
| **5** | AI: scoped tools, auth, confirmation, logging |
| **6** | Reports: generalized PDFs |
| **7** | Security: RBAC, isolation, policies |
| **8** | Cleanup: terminology audit, dead code removal |
| **9** | Documentation: README, CHANGELOG, architecture |
| **10** | Testing + v1.0.0 acceptance |

**Execute one phase at a time** unless explicitly asked to continue.

---

## Code reuse guidelines

**Keep** when the behavior is generic:

- Task deliverable upload and review
- Comments and activity log
- Notifications (retarget URLs only)
- Fortify auth, Inertia + Svelte, Filament for platform admin
- Pest tests (rewrite assertions for new domain)

**Delete** when university-specific or unused:

- Events, attendance, certificates, volunteer hours
- Public catalog and club join forms with academic fields
- Orphan AI tools and `TeamHubDemoController`
- `Support/TeamHub/` compatibility layer (replace with Services in Phase 4)

**Generalize** when the idea is sound but the shape is wrong:

- Membership requests (workspace join, not club application)
- PDF exports (team/project reports, not volunteer hours)
- Project updates (not “news” for public clubs)

---

## Testing expectations

- Run `composer test` after each phase
- Replace legacy tests that assert `Club`/`Committee` with Workspace/Project tests
- Add `LegacyTerminologyAuditTest` in Phase 8 — zero forbidden terms in `app/`, `resources/js/`, `lang/`

---

## Documentation expectations

| Phase | Docs |
|-------|------|
| 0 | `docs/PRODUCT_VISION.md`, `docs/DOMAIN_MODEL.md`, this file |
| 9 | README, CHANGELOG, ARCHITECTURE, ROADMAP, screenshots |

Do not rewrite README in Phase 0 — that is Phase 9.

---

## Questions before starting Phase 1

If any of the following are unclear, ask the product owner before coding:

1. Single-workspace default for demo vs multi-workspace from day one?
2. Email invitations in v1.0 or membership-request-only?
3. Squash migrations at v1.0.0 or incremental rename migration?

**Phase 0 complete does not require Phase 1 to start immediately.**
