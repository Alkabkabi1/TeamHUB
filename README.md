# TeamHUB

[![CI](https://github.com/Alkabkabi1/TeamHUB/actions/workflows/tests.yml/badge.svg)](https://github.com/Alkabkabi1/TeamHUB/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](./LICENSE)

> Arabic-first teamwork platform for small organizations — workspaces, projects, tasks, deliverables, and files in one place.

**Repository:** [github.com/Alkabkabi1/TeamHUB](https://github.com/Alkabkabi1/TeamHUB)  
**Release candidate:** `v1.0.0-rc1` — see [CHANGELOG.md](./CHANGELOG.md) and [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md)  
**Status:** Phases 1–7 complete — ready for Phase 8 (deploy & pilot). See [docs/PHASE_7_CLOSURE.md](./docs/PHASE_7_CLOSURE.md).  
**License:** MIT — see [LICENSE](./LICENSE) and [NOTICE](./NOTICE)

---

## What this is

TeamHUB started from the Ruwad university-clubs codebase and now operates as a **team project hub** aimed at NGOs, Arabic-speaking startups, and small program teams. The current product centers on workspaces, projects, tasks, deliverables, comments, notifications, and a safe task-first AI assistant.

### Current TeamHUB capabilities

| Area | Description |
|------|-------------|
| **Workspaces & projects** | Org container + project teams (`Workspace`, `Project`) |
| **Tasks** | Title, description, assignee, due date, priority, status |
| **Deliverables on complete** | Upload a file, paste a link (Figma, Drive, GitHub PR), or add notes when finishing work |
| **Review workflow** | `Todo` → `In Progress` → `Review` → `Done` — submit output, lead approves |
| **Comments & activity** | Lightweight collaboration and audit trail on each task |
| **My work** | Unified view of tasks assigned to you across projects |
| **AI assistant** | Safe, confirm-before-write task queries and mutations |
| **Arabic / RTL** | Bilingual UI inherited from the Ruwad foundation |

### Why deliverables matter

Most task apps stop at a checkbox. TeamHUB answers the question teams actually ask: **"What was produced?"** When an assignee completes a task, they attach a deliverable, the task moves to **Review**, and a project lead approves or requests changes.

See [§8.1 Task deliverables & review workflow](./PLATFORM_REUSE_AND_PIVOT_PLAN.md#81-task-deliverables--review-workflow) in the plan for full UX, schema, and implementation notes.

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13 (PHP 8.4) |
| Runtime | Laravel Octane + RoadRunner |
| Frontend | Inertia.js v3 + Svelte 5 |
| Styling | Tailwind CSS v4, bits-ui |
| Admin | Filament v4 |
| Auth | Laravel Fortify |
| Media | Spatie Media Library (task deliverable uploads) |
| AI | Laravel AI SDK |
| Testing | Pest v4 |
| Static analysis | PHPStan + Larastan (level 5) |

**Supported versions:** PHP 8.4 (Composer constraint `^8.3`), Node.js 22.

---

## Getting started

```bash
git clone https://github.com/Alkabkabi1/TeamHUB.git
cd TeamHUB
composer setup    # install, .env, migrate, build
composer dev      # server + queue + logs + Vite (on Windows: run php artisan serve and npm run dev separately)
composer test     # lint + Pest
composer ci:check # full CI gate
composer analyse  # PHPStan static analysis
```

Copy `.env.example` to `.env` and configure `APP_NAME=TeamHUB`, database, and mail before running migrations.

### Local-first dev profile

The default local setup is tuned for **no real DB server**:

- SQLite file database at `database/database.sqlite`
- `FILESYSTEM_DISK=local`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `MAIL_MAILER=log`

Create the SQLite file once if it does not exist:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
ni database/database.sqlite -ItemType File
```

Run the app in separate terminals if you prefer:

```bash
php artisan serve
npm run dev
```

### Demo accounts

When `DEMO_QUICK_LOGIN=true` (local default), visit `/` to pick a demo role. Seeded accounts include `admin@teamhub.test` and workspace/project leads — see seeders in `database/seeders/`. **Disable demo login in production** (`DEMO_QUICK_LOGIN=false`).

### Documentation

**Product & engineering**

- [Phase 1 closure report](./docs/PHASE_1_CLOSURE.md)
- [Phase 4 closure report](./docs/PHASE_4_CLOSURE.md)
- [Phase 5 closure report](./docs/PHASE_5_CLOSURE.md)
- [Phase 6 closure report](./docs/PHASE_6_CLOSURE.md)
- [Phase 7 closure report](./docs/PHASE_7_CLOSURE.md)
- [Product vision](./docs/PRODUCT_VISION.md)
- [Domain model](./docs/DOMAIN_MODEL.md)
- [Engineering principles](./docs/ENGINEERING_PRINCIPLES.md)

**Operations & release**

- [Release checklist](./RELEASE_CHECKLIST.md) — pre-deploy cutover
- [Production verification](./PRODUCTION_VERIFICATION_CHECKLIST.md) — post-deploy QA
- [Operations runbook](./OPERATIONS_RUNBOOK.md) — day-2 ops
- [Backup & recovery](./BACKUP_AND_RECOVERY.md)
- [Deploy examples](./deploy/examples/) — nginx, Supervisor, systemd
- [Changelog](./CHANGELOG.md)
- [Deploy runbook](./TEAMHUB_DEPLOY_RUNBOOK.md)
- [User guide](./TEAMHUB_USER_GUIDE.md)

**Contributing**

- [Contributing guide](./CONTRIBUTING.md)
- [Security policy](./SECURITY.md)
- [Code of conduct](./CODE_OF_CONDUCT.md)

### Domain vocabulary

The product uses **Workspace → Project → Task** everywhere: models, routes, Inertia payloads, permissions, and UI filenames.

| Layer | Canonical term |
|-------|----------------|
| Organization | `Workspace` |
| Team within org | `Project` |
| Work item | `Task` |
| Org membership | `WorkspaceMembership` |
| Team membership | `ProjectMembership` |

Historical database migrations may still reference legacy table names (`clubs`, `committees`) from the re-engineering migration — see [DOMAIN_MODEL.md](./docs/DOMAIN_MODEL.md).

---

## Project structure

```
app/
  Ai/Tools/          AI assistant tools
  Filament/          Staff admin panel
  Http/Controllers/  Inertia + web controllers
  Models/            Eloquent models
resources/js/pages/  Svelte pages (Inertia)
lang/                ar/ and en/ translations
database/            migrations, factories, seeders
tests/               Pest feature & unit tests
```

---

## Roadmap

| Phase | Focus | Status |
|-------|-------|--------|
| **0** | Product vision, domain model, engineering principles | Done |
| **1** | Domain re-engineering (Workspace → Project → Task) | Done |
| **1.5** | P0 hardening (AI, Inertia contract, demo accounts) | Done |
| **2** | UI / surface rename | Done |
| **3** | Runtime contract canonicalization | Done |
| **3.5** | Repository cleanup & dead code removal | Done |
| **4** | Production readiness audit | Done — [closure report](./docs/PHASE_4_CLOSURE.md) |
| **5** | Release candidate & open-source readiness (`v1.0.0-rc1`) | Done — [closure report](./docs/PHASE_5_CLOSURE.md) |
| **6** | Production deployment readiness & operational validation | Done — [closure report](./docs/PHASE_6_CLOSURE.md) |
| **7** | Polish, i18n & tests | Done — [closure report](./docs/PHASE_7_CLOSURE.md) |
| **8** | Deploy & pilot | Planned |

Legacy pivot checklist: [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md)

---

## Attribution

Based on [Ruwad](https://github.com/Weaam-02/ruwad), MIT License. See [NOTICE](./NOTICE).
