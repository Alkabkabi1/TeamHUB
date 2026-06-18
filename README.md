# TeamHUB

> Arabic-first teamwork platform for small organizations — workspaces, projects, tasks, deliverables, and files in one place.

**Repository:** [github.com/Alkabkabi1/TeamHUB](https://github.com/Alkabkabi1/TeamHUB)  
**Status:** Fork in progress (from [Ruwad](https://github.com/Weaam-02/ruwad))  
**License:** MIT — see [LICENSE](./LICENSE) and [NOTICE](./NOTICE)  
**Planning doc:** [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md)

---

## What this is

TeamHUB reuses the Ruwad university-clubs codebase as a foundation for a **team project hub** aimed at NGOs, Arabic-speaking startups, and small program teams. The stack is already production-grade; the new work is the **task domain** and teamwork workflows.

### Planned v1 features

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

Most task apps stop at a checkbox. TeamHUB answers the question teams actually ask: **"What was produced?"** When an assignee completes a task, a modal captures the deliverable; the task moves to **Review** until a project lead approves.

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

Implementation is phased over ~10–14 weeks. Current focus:

1. **Phase 0** — Branding strip, LICENSE, glossary
2. **Phase 1** — Task model, migrations, policies (including deliverable fields + Spatie media)
3. **Phase 2** — Task CRUD UI + complete-task modal + review approve/reject
4. **Phases 3–8** — Project shell, my work, comments, AI tools, polish, pilot

Full checklists: [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md)

---

## Attribution

Based on [Ruwad](https://github.com/Weaam-02/ruwad), MIT License. See [NOTICE](./NOTICE).
