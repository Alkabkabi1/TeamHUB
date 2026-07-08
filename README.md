<p align="center">
  <img src="./public/teamhub-icon.svg" alt="TeamHUB" width="72" height="72" />
</p>

<h1 align="center">TeamHUB</h1>

<p align="center">
  <strong>Arabic-first teamwork platform for small organizations</strong><br />
  Workspaces, projects, tasks, deliverables, and review — in one place.
</p>

<p align="center">
  <a href="https://github.com/Alkabkabi1/TeamHUB/actions/workflows/tests.yml"><img src="https://github.com/Alkabkabi1/TeamHUB/actions/workflows/tests.yml/badge.svg" alt="CI" /></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="MIT License" /></a>
  <a href="https://github.com/Alkabkabi1/TeamHUB/releases/tag/v1.0.0-rc1"><img src="https://img.shields.io/github/v/release/Alkabkabi1/TeamHUB?include_prereleases&label=release" alt="Release" /></a>
</p>

<p align="center">
  <a href="https://github.com/Alkabkabi1/TeamHUB/releases/tag/v1.0.0-rc1"><strong>v1.0.0-rc1</strong></a>
  ·
  <a href="./TEAMHUB_USER_GUIDE.md">User guide</a>
  ·
  <a href="./RELEASE_CHECKLIST.md">Deploy</a>
  ·
  <a href="./docs/README.md">Documentation</a>
</p>

---

## Screenshots

| Task list (Arabic, RTL) | Task detail & review |
| --- | --- |
| ![Arabic task list](./docs/screenshots/04-task-list-ar.png) | ![Task detail with deliverable review](./docs/screenshots/05-task-detail-ar.png) |

| My work | Mobile task list | English locale |
| --- | --- | --- |
| ![My Tasks dashboard](./docs/screenshots/02-my-tasks-ar.png) | ![Mobile task list](./docs/screenshots/06-task-list-mobile-ar.png) | ![English task list](./docs/screenshots/07-task-list-en.png) |

> Live demo URL: add your staging link here after Phase 8 deploy.

---

## What this is

TeamHUB is a **standalone, Arabic-first platform for team and project work** aimed at NGOs, Arabic-speaking startups, and small program teams.

It began as a **re-engineering** of the [Ruwad](https://github.com/Weaam-02/ruwad) university-clubs codebase into a generic teamwork product built around **Workspace → Project → Task**. See [NOTICE](./NOTICE) for attribution.

### One-line pitch

**Arabic-first teamwork where completing a task means submitting real output and getting lead approval — not checking a box.**

### Core capabilities

| Area | What you get |
| --- | --- |
| **Workspaces & projects** | Organization container + delivery teams |
| **Tasks** | Assignee, due date, priority, status, comments, activity |
| **Deliverables** | File, link, or notes when work is submitted for review |
| **Review workflow** | `Todo` → `In Progress` → `Review` → `Done` |
| **My work** | Cross-project view of tasks assigned to you |
| **AI assistant** | Task-aware tools with confirm-before-write mutations |
| **Arabic / English** | Bilingual UI with RTL support |

---

## Quick start

```bash
git clone https://github.com/Alkabkabi1/TeamHUB.git
cd TeamHUB
composer setup
composer dev
```

On Windows, run `php artisan serve` and `npm run dev` in separate terminals instead of `composer dev`.

Copy `.env.example` to `.env`. Local defaults use SQLite at `database/database.sqlite`:

```powershell
ni database/database.sqlite -ItemType File
```

| Command | Purpose |
| --- | --- |
| `composer test` | Lint + Pest (339 tests) |
| `composer ci:check` | Full CI gate |
| `composer analyse` | PHPStan static analysis |

### Demo login

When `DEMO_QUICK_LOGIN=true` (local default), open `/` and pick a demo role. **Disable in production** (`DEMO_QUICK_LOGIN=false`).

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.4) |
| Runtime | Laravel Octane + RoadRunner |
| Frontend | Inertia.js v3 + Svelte 5 |
| Styling | Tailwind CSS v4 |
| Admin | Filament v4 |
| Auth | Laravel Fortify |
| AI | Laravel AI SDK |
| Testing | Pest v4 + PHPStan |

---

## Deploy

Production profile: **Linux VPS**, **PHP 8.4**, **Octane/RoadRunner**, **Redis**, **PostgreSQL 15+** (or MySQL 8+).

1. [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) — pre-deploy cutover
2. [deploy/deploy.sh](./deploy/deploy.sh) — build, migrate, cache, Octane reload
3. [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md) — post-deploy QA

Example nginx, Supervisor, and systemd units: [deploy/examples/](./deploy/examples/).

---

## Documentation

| Audience | Start here |
| --- | --- |
| **Users** | [TEAMHUB_USER_GUIDE.md](./TEAMHUB_USER_GUIDE.md) |
| **Operators** | [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md) |
| **Contributors** | [CONTRIBUTING.md](./CONTRIBUTING.md) |
| **Engineers** | [docs/README.md](./docs/README.md) |

---

## Roadmap

| Phase | Focus | Status |
| --- | --- | --- |
| **0–7** | Vision → domain → polish → `v1.0.0-rc1` | Done |
| **8** | Deploy & pilot | In progress |
| **9** | Pilot feedback & prioritization | Planned |
| **10** | Navigation & role simplification | Done |

Release history: [CHANGELOG.md](./CHANGELOG.md)

Possible enhancements after v1.0 are captured below in **[Future improvements (post-v1)](#future-improvements-post-v1)**. Those items are ideas only — not commitments or a delivery schedule.

---

## Future improvements (post-v1)

> **Scope note:** TeamHUB v1.0 architecture, domain model, authorization, and navigation are considered stable after Phase 10. Everything in this section is **exploratory** — possible directions for later releases, informed by pilot feedback. Nothing here is planned, scheduled, or guaranteed.

TeamHUB is not aiming to become a generic Jira or Trello clone. Future work should deepen what makes the product distinctive: **a task is not complete until a real deliverable is submitted and approved.**

### Product structure

#### Optional workspace lead

Workspace Lead may become an **optional** organizational layer rather than a required step for every deployment.

| Deployment size | Typical chain |
| --- | --- |
| **Small teams** | Platform Admin → Project Lead → Member |
| **Larger organizations** | Platform Admin → Workspace Lead → Project Lead → Member |

The Workspace Lead role should remain **supported** — it improves scalability, reporting, and governance for multi-project organizations — but it should not be mandatory when a team only needs a flat admin → project → member structure.

Introducing or omitting Workspace Leads should not force changes to tasks, deliverables, review workflow, or project-level permissions.

### User experience

- **Role-aware onboarding** — first-run guidance tailored to admin, workspace lead, project lead, and member
- **Helpful empty states** — clear next steps instead of blank pages when a workspace, project, or task list is empty
- **Role-specific dashboards** — each persona sees what they need to act on (reviews pending, assignments due, join requests, etc.)
- **Improved mobile experience** — touch-friendly task submission and review on phones
- **Skeleton loading states** — perceived performance while data loads
- **Consistent motion** — restrained animations and transitions that respect reduced-motion preferences
- **Keyboard shortcuts** — power-user navigation for frequent actions (review, assign, search)
- **Command palette** — quick launcher (`Ctrl+K` / `⌘K`) for jumping to projects, tasks, and actions

### Productivity

- **Global search** — find workspaces, projects, tasks, and members from one entry point
- **Dashboard widget customization** — let leads pin the metrics and lists they care about
- **Rich task timeline** — auditable history of status changes, assignments, reviews, and deliverable submissions
- **Rich deliverables** — attach or link GitHub PRs, Figma files, Google Docs, videos, and other proof-of-work artifacts beyond a single file or URL
- **Better comments** — `@mentions`, reactions, and threaded discussion tied to review context
- **Calendar / timeline views** — due dates and review deadlines across projects without replacing the deliverable-first task model

### Collaboration

- **Activity feed** — recent workspace and project events (assignments, submissions, approvals, membership changes)
- **Notification categorization** — group by review requests, mentions, assignments, and workspace admin items
- **Weekly summaries** — digest of what moved forward and what still needs a deliverable or approval
- **Project activity history** — durable log of who did what, supporting accountability in distributed teams

### Reporting

**Workspace-level**

- Task completion rate (deliverable submitted → approved → done)
- Average review time (submission to lead decision)
- Active projects and stalled work
- Overdue tasks and aging review queue

**Platform-level** (admin / operator)

- Workspace growth and activation
- User growth and engagement
- Storage usage (media and deliverables)
- AI assistant usage and tool adoption

Reports should highlight **workflow health** (deliverables flowing through review), not vanity metrics like raw task counts.

### AI

Future AI capabilities should be **workflow-aware**, not a generic chatbot bolted onto the sidebar.

Examples aligned with TeamHUB's model:

- Surface overdue tasks and items stuck in review
- Summarize project progress from real deliverable activity
- Suggest task assignments based on workload and membership (with human confirmation)
- Generate weekly workspace summaries for leads
- Detect bottlenecks where work piles up before approval

The assistant should help teams **move deliverables through review** — suggest actions, explain status, and prepare drafts — while keeping humans in control of approvals and mutations.

### Guiding principle

Future development should prioritize **strengthening TeamHUB's identity** rather than adding features for feature's sake. Every enhancement should improve the deliverable-review workflow and reinforce accountability — not simply increase feature count.

---

## Contributing

Contributions welcome. Read [CONTRIBUTING.md](./CONTRIBUTING.md), [SECURITY.md](./SECURITY.md), and [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md) before opening a PR.

---

## Attribution

Based on [Ruwad](https://github.com/Weaam-02/ruwad), MIT License. See [NOTICE](./NOTICE) and [LICENSE](./LICENSE).

For the original pivot planning notes and legacy mapping tables, see [PLATFORM_REUSE_AND_PIVOT_PLAN.md](./PLATFORM_REUSE_AND_PIVOT_PLAN.md).
