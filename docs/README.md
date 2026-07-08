# TeamHUB documentation index

Central index for product, engineering, operations, and release documentation.

---

## Product

| Document | Description |
| --- | --- |
| [PRODUCT_VISION.md](./PRODUCT_VISION.md) | Product principles and positioning |
| [DOMAIN_MODEL.md](./DOMAIN_MODEL.md) | Workspace → Project → Task model |
| [ENGINEERING_PRINCIPLES.md](./ENGINEERING_PRINCIPLES.md) | Build rules and conventions |
| [../TEAMHUB_USER_GUIDE.md](../TEAMHUB_USER_GUIDE.md) | End-user workflows |

---

## Phase closure reports

| Phase | Report |
| --- | --- |
| 1 | [PHASE_1_CLOSURE.md](./PHASE_1_CLOSURE.md) |
| 4 | [PHASE_4_CLOSURE.md](./PHASE_4_CLOSURE.md) |
| 5 | [PHASE_5_CLOSURE.md](./PHASE_5_CLOSURE.md) |
| 6 | [PHASE_6_CLOSURE.md](./PHASE_6_CLOSURE.md) |
| 7 | [PHASE_7_CLOSURE.md](./PHASE_7_CLOSURE.md) |
| 7 checklist | [PHASE_7_CHECKLIST.md](./PHASE_7_CHECKLIST.md) |

---

## Operations & release

| Document | Description |
| --- | --- |
| [../RELEASE_CHECKLIST.md](../RELEASE_CHECKLIST.md) | Pre-deploy cutover |
| [../PRODUCTION_VERIFICATION_CHECKLIST.md](../PRODUCTION_VERIFICATION_CHECKLIST.md) | Post-deploy QA |
| [../OPERATIONS_RUNBOOK.md](../OPERATIONS_RUNBOOK.md) | Day-2 operations |
| [../BACKUP_AND_RECOVERY.md](../BACKUP_AND_RECOVERY.md) | Backup strategy |
| [../TEAMHUB_DEPLOY_RUNBOOK.md](../TEAMHUB_DEPLOY_RUNBOOK.md) | Deploy overview |
| [../deploy/examples/](../deploy/examples/) | nginx, Supervisor, systemd |
| [../CHANGELOG.md](../CHANGELOG.md) | Release history |

---

## Planning & history

| Document | Description |
| --- | --- |
| [../README.md#future-improvements-post-v1](../README.md#future-improvements-post-v1) | Post-v1 ideas (not commitments) |
| [PHASE_10_NAV_REDESIGN.md](./PHASE_10_NAV_REDESIGN.md) | Phase 10 navigation & role simplification |
| [../PLATFORM_REUSE_AND_PIVOT_PLAN.md](../PLATFORM_REUSE_AND_PIVOT_PLAN.md) | Original pivot plan and legacy mapping |
| [../PHASE_7_POLISH_NOTES.md](../PHASE_7_POLISH_NOTES.md) | Deferred polish notes |

---

## Screenshots

README screenshots live in [screenshots/](./screenshots/). To regenerate:

```bash
php artisan migrate:fresh --seed
php scripts/seed-for-screenshots.php
npm run build
php artisan serve   # separate terminal
node scripts/capture-screenshots.mjs
```

Requires a one-off `playwright` install (`npm install --no-save playwright`).
