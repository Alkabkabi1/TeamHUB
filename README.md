# TeamHUB

> Arabic-first teamwork platform for small organizations — workspaces, projects, tasks, deliverables, and files in one place.

**Repository:** [github.com/Alkabkabi1/TeamHUB](https://github.com/Alkabkabi1/TeamHUB)  
**Status:** TeamHUB MVP implemented locally; next roadmap handoff is Phase 8 deploy/pilot  
**License:** MIT — see [LICENSE](./LICENSE) and [NOTICE](./NOTICE)  
**Planning doc:** [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md)

---

## What this is

TeamHUB started from the Ruwad university-clubs codebase and now operates as a **team project hub** aimed at NGOs, Arabic-speaking startups, and small program teams. The current product centers on workspaces, projects, tasks, deliverables, comments, notifications, and a safe task-first AI assistant.

### Current TeamHUB capabilities

| Area | Description |
|------|-------------|
| **Workspaces & projects** | Org container + project teams (mapped from Club → Workspace, Committee → Project) |
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

---

## Getting started

```bash
git clone https://github.com/Alkabkabi1/TeamHUB.git
cd TeamHUB
composer setup    # install, .env, migrate, build
composer dev      # server + queue + logs + Vite (on Windows: run php artisan serve and npm run dev separately)
composer test     # lint + Pest
composer ci:check # full CI gate
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

### TeamHUB docs

- [TeamHUB user guide](./TEAMHUB_USER_GUIDE.md)
- [TeamHUB deploy runbook](./TEAMHUB_DEPLOY_RUNBOOK.md)
- [Validation checklist](./VALIDATION_READINESS_CHECKLIST.md)

### Internal glossary

For the current MVP, TeamHUB reuses the existing domain models internally:

| Existing model | TeamHUB meaning |
|----------------|-----------------|
| `Club` | Workspace |
| `Committee` | Project |
| `ClubMembership` | Workspace membership |
| `CommitteeMembership` | Project membership |
| `Post` | Project update |
| `ClubResource` | Project file |

### Design preview (TeamHUB UI)

Static preview pages for testing the design system (no auth required):

| Page | URL |
|------|-----|
| Hub index | `/preview/team-hub` |
| Dashboard | `/preview/team-hub/dashboard` |
| Tasks | `/preview/team-hub/tasks` |
| Projects | `/preview/team-hub/projects` |
| Deliverable workflow | `/preview/team-hub/deliverable` |

Use the theme toggle in the sidebar to switch light/dark mode. Preview pages are isolated from the legacy app chrome.

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

Implementation has been delivered in phases. Current state:

1. **Phases 0–2** — Branding pivot, task model, deliverables, and review workflow are complete
2. **Phases 3–6** — Workspace/project shells, member dashboard, comments, notifications, and task-first AI assistant are complete
3. **Phase 7** — Polish, i18n, docs, and validation are the current release-hardening pass
4. **Phase 8** — Next handoff: deployment, pilot rollout, and operational follow-up

Full checklists: [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md)

---

## Attribution

Based on [Ruwad](https://github.com/Weaam-02/ruwad), MIT License. See [NOTICE](./NOTICE).
