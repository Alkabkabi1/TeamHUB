# Platform Reuse & Product Pivot Plan

> Strategic planning document for reusing the Ruwad codebase as a foundation for new products — including licensing, alternative product directions, and a detailed plan to pivot toward **project tracking and teamwork**.

**Status:** Draft · **Last updated:** June 2026  
**Source repo:** [github.com/Weaam-02/ruwad](https://github.com/Weaam-02/ruwad)

---

## Table of contents

1. [Executive summary](#1-executive-summary)
2. [What this foundation is](#2-what-this-foundation-is)
3. [Reusing the frameworks](#3-reusing-the-frameworks)
4. [Reusing this codebase](#4-reusing-this-codebase)
5. [License and credits](#5-license-and-credits)
6. [Ownership caveat](#6-ownership-caveat)
7. [Alternative product directions](#7-alternative-product-directions)
8. [Recommended direction: project tracking & teamwork](#8-recommended-direction-project-tracking--teamwork)
   - [8.1 Task deliverables & review workflow](#81-task-deliverables--review-workflow)
9. [Gap analysis](#9-gap-analysis)
10. [Conceptual model mapping](#10-conceptual-model-mapping)
11. [Detailed implementation plan](#11-detailed-implementation-plan)
12. [What to keep, repurpose, and drop](#12-what-to-keep-repurpose-and-drop)
13. [Validation and success metrics](#13-validation-and-success-metrics)
14. [Risks and mitigations](#14-risks-and-mitigations)
15. [Decision log](#15-decision-log)
16. [Appendix: tech stack reference](#16-appendix-tech-stack-reference)

---

## 1. Executive summary

Ruwad is not just a university clubs app. Under the hood it is a **membership + scoped permissions + events + approvals + branded org pages + certificates + AI operations** platform built on a modern Laravel + Svelte stack.

**Key conclusions from our planning discussion:**

| Question | Answer |
|----------|--------|
| Can we use the frameworks in new projects? | **Yes** — Laravel, Svelte, Inertia, Filament, etc. are all open source. |
| Can we fork this repo as a starter? | **Yes** — the project declares **MIT** license. |
| Do we need credits? | **In code:** keep MIT copyright + license notice. **In UI:** not required, optional. |
| Can we build something outside uni/UQU apps? | **Yes** — many verticals fit with minimal conceptual rework. |
| Can we do project tracking & teamwork? | **Yes** — strong platform reuse (~40%), but tasks/boards are new core domain (~60%). |
| Should we compete head-on with Asana/Linear? | **No** — niche down (Arabic-first, NGOs, program delivery, projects + events + files). |

**Recommended path:** Pivot toward a **team project hub** — workspaces, projects, tasks, milestones, team feed, files, and optional time logging — leveraging existing auth, RBAC, dashboards, AI assistant patterns, and Arabic/RTL support.

**Estimated MVP timeline:** 10–14 weeks for a focused v1 (list view, not Kanban; no sprints/integrations).

---

## 2. What this foundation is

Ruwad (رُوَّاد الأندية) was built as a student clubs platform for Umm Al-Qura University. Its *domain* is university-specific, but its *architecture* is a reusable multi-tenant-style org platform.

### Core capabilities already built

| Capability | Description |
|------------|-------------|
| **Organizations** | Clubs with themes, branding, public pages |
| **Sub-units** | Committees nested inside clubs |
| **Membership** | Join applications, approval/rejection, roster management |
| **Scoped RBAC** | Role → capability union per club/committee; Gates + policies |
| **Events** | Scheduling, capacity, status, registration |
| **Attendance** | QR-based check-in workflow |
| **Volunteer hours** | Log → supervisor approves |
| **News** | Published posts, public feed |
| **Resources** | File/library per org |
| **Certificates** | Drag-and-drop template designer, Arabic PDF generation |
| **AI assistant** | Role-scoped tools, confirm-before-write for mutations |
| **Dashboards** | Role-based student/manager views |
| **Admin** | Filament staff panel |
| **i18n** | Arabic-first RTL + English |

### What it is *not*

- A generic CRUD starter kit
- A project management tool (no tasks, boards, assignees, dependencies)
- A real-time chat or social network
- An e-commerce or marketplace platform

---

## 3. Reusing the frameworks

All major dependencies are standard open-source packages. You can use them in any new project without asking permission.

| Layer | Technology | Typical license |
|-------|------------|-----------------|
| Backend | Laravel 13 (PHP 8.4) | MIT |
| Runtime | Laravel Octane + RoadRunner | MIT |
| Frontend | Inertia.js v3 + Svelte 5 | MIT |
| Styling | Tailwind CSS v4, bits-ui | MIT |
| Admin | Filament v4 | MIT |
| Auth | Laravel Fortify | MIT |
| Routes | Laravel Wayfinder | MIT |
| AI | Laravel AI SDK | MIT |
| Media | Spatie Media Library | MIT |
| PDF | DomPDF + Arabic shaping | LGPL / MIT (check package) |
| Testing | Pest v4 | MIT |

**Practical rule:** Use freely in commercial and non-commercial projects. You do not need to display framework credits in your UI. Dependency licenses are satisfied via `composer.lock` / `package-lock.json`.

---

## 4. Reusing this codebase

The repo declares **MIT** in `composer.json` and `README.md`. That means you may:

- Copy, modify, and distribute the code
- Use it in closed-source or commercial products
- Build entirely new products on top of it

### Best ways to reuse

1. **Fork and pivot** — Strip UQU/Ruwad branding, reshape domain models, ship a new product on the same stack.
2. **Extract patterns** — Auth layout, Filament setup, scoped Gates, AI confirm-flow, certificate designer, RTL/i18n patterns.
3. **Vertical re-skin** — Rename Club → Organization, Committee → Program; keep workflows intact (NGO, training center, etc.).

### What makes this a strong starter (vs. `laravel new`)

- Inertia + Svelte 5 wired and production-tested
- Scoped team permissions (hard to get right from scratch)
- Filament admin already integrated
- AI assistant with safe write confirmation
- Arabic PDF + RTL UI
- Octane/RoadRunner deployment config
- Pest test suite and CI patterns

---

## 5. License and credits

### MIT requirements

If you reuse **substantial portions** of this codebase, MIT requires:

1. Include the **copyright notice** and **MIT license text** in copies or derivative works.
2. That is the full legal obligation — no revenue share, no copyleft, no mandatory UI badge.

### Recommended attribution (new project)

Add a `LICENSE` file to the new project:

```
MIT License

Copyright (c) [year] [original Ruwad authors]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

Optional `NOTICE` or README line:

> Based on [Ruwad](https://github.com/Weaam-02/ruwad), MIT License.

### Action item for this repo

Add a root `LICENSE` file (currently missing despite MIT being declared) so forks have an unambiguous legal artifact.

---

## 6. Ownership caveat

Before treating the codebase as freely reusable IP, confirm:

- **Who holds copyright?** Remote: `github.com/Weaam-02/ruwad`.
- **Was this a graduation or university-funded project?** Institutions may impose IP policies beyond MIT.
- **Multiple contributors?** All authors should agree on licensing if not already covered.

If you are the author and chose MIT intentionally, you are clear. If not, confirm with the repo owner or institution first.

---

## 7. Alternative product directions

Ideas explored that fit the foundation **without** being university apps.

### Tier 1 — Highest fit (least rework)

| Idea | Why it fits | Reuse score |
|------|-------------|-------------|
| **NGO / charity volunteer hub** | Hours, approvals, events, certificates, membership | ~90% |
| **Training center / bootcamp** | Enrollment, sessions, attendance, completion certs | ~85% |
| **Professional association / guild** | Chapters, events, CPD hours, member roster | ~85% |
| **Mosque / community center** | Events, volunteers, announcements, Arabic PDFs | ~85% |
| **Conference / hackathon ops** | Registration, QR check-in, attendee certificates | ~75% |

### Tier 2 — Strong fit with clearer niche

| Idea | Why it fits |
|------|-------------|
| **Corporate ERG / internal communities** | Scoped groups, events, resources, manager approvals |
| **Sports club / league admin** | Teams, fixtures, roster, branded pages |
| **Coworking / maker space** | Membership, workshops, resource library |
| **Youth program / summer camp** | Registration, attendance, completion docs |
| **Municipal / foundation programs** | Public events, volunteer tracking, reports |

### Tier 3 — Differentiator plays

| Idea | Moat |
|------|------|
| **Certificate-as-a-service** | Drag-and-drop designer + Arabic PDF shaping |
| **AI ops copilot for community managers** | Confirm-before-write, role-scoped automation |
| **Arabic-first community SaaS** | RTL, bilingual UI, underserved GCC market |

### Poor fit (avoid or start fresh)

- Pure e-commerce / marketplace
- Real-time chat / social network
- Heavy CRM or full project management (without building task domain)
- Content-only blog or portfolio site

### Idea validation filter

Score each idea 0–5 on:

1. Who applies and who approves? (membership workflow)
2. Recurring events with attendance?
3. Proof of participation needed? (certificates)
4. Public updates and file sharing?
5. Arabic/RTL a requirement?

**3+ yes answers** → strong fit for this foundation.

---

## 8. Recommended direction: project tracking & teamwork

### Verdict

| Aspect | Assessment |
|--------|------------|
| Technically feasible? | **Yes** |
| Already a PM tool? | **No** — platform shell yes, task domain no |
| Platform reuse | ~40% (auth, RBAC, members, feed, files, AI, admin) |
| New work | ~60% (tasks, boards/lists, assignees, comments, notifications) |
| Competitive strategy | **Niche** — do not clone Asana globally |

### Product positioning (working title: **Team Project Hub**)

> Arabic-first teamwork platform for small organizations that need **projects, tasks, milestones, updates, and files** in one place — without the complexity of enterprise PM tools.

### Target users (v1)

- Small NGOs and nonprofits (5–50 people)
- Arabic-speaking startups and agencies
- Program managers running initiatives with deadlines and deliverables
- Teams that also run events/workshops alongside project work

### Wedge (why us vs. Trello/Asana/Notion)

1. **Arabic-first** — RTL-native UI, bilingual, Arabic PDF exports
2. **Program delivery** — projects + milestones + optional time logging + reports
3. **All-in-one** — tasks + team feed + file library (not three tools)
4. **AI admin** — safe, gated task creation and status queries
5. **Deliverables on completion** — every finished task can answer *"What was actually produced?"* (file, link, or notes) — not just a checked box

### What we are NOT building in v1

- Full Asana/Linear feature parity
- Sprints, epics, story points, velocity charts
- Gantt charts and dependency graphs
- Slack/Jira/GitHub integrations
- Native mobile apps
- Real-time collaborative editing

---

### 8.1 Task deliverables & review workflow

Most task apps stop at a checkbox. Teams then ask *"Where's the logo?"* / *"Which version?"* and the answer is lost in WhatsApp. This pivot treats **deliverables** as first-class — the feature that makes the product feel like a **work platform**, not a fancy to-do list.

#### Problem vs. solution

| Typical task app | Team Project Hub |
|------------------|------------------|
| ☐ Design logo → ☑ Design logo | Task → **Deliverable** → Review → Done |
| No record of output | Audit trail: who submitted what, when |
| "Done" means ambiguous | "Done" means approved with evidence |

#### Status workflow (v1)

Use four statuses — not three — so completion is a handoff, not a dead end:

```
Todo → In Progress → Review → Done
```

| Status | Meaning |
|--------|---------|
| `todo` | Not started |
| `in_progress` | Assignee is working |
| `review` | Assignee submitted a deliverable; project lead reviews |
| `done` | Lead approved (or task closed without review, if policy allows) |

**Flow:**

```
Assignee clicks "Submit for review"
        ↓
Complete modal: upload file OR paste link OR notes (project-configurable required/optional)
        ↓
Task → status: review
        ↓
Project lead reviews deliverable
        ↓
Approve → status: done   |   Request changes → status: in_progress
```

#### Complete-task modal (UX)

When the assignee marks a task complete (or submits for review), show a modal:

```
Deliverable (optional or required — per project setting)

[ Upload File ]   [ Paste Link ]   [ Notes ]

[ Submit for Review ]   [ Cancel ]
```

**Supported deliverable types (examples):** PDF, Figma link, GitHub PR, Google Drive link, ZIP, screenshot.

**After submission**, task detail shows:

```
✓ Completed (or: In Review)

Deliverable: landing-page-v2.fig
Submitted by Ahmed · June 18, 2026
```

#### MVP data model (keep it simple)

One deliverable per task in v1 — enough for portfolio demo and pilot teams:

```
Task
├── title
├── description
├── assignee
├── due_date
├── status (todo | in_progress | review | done)
├── deliverable_url (nullable)      — external link (Figma, Drive, PR)
├── deliverable_notes (text, nullable)
├── deliverable_submitted_at (timestamp, nullable)
├── deliverable_submitted_by (FK users, nullable)
└── media (Spatie) — single uploaded file via existing attachment pattern
```

**Rule:** At least one of `deliverable_url`, uploaded file, or `deliverable_notes` when deliverable is required.

#### Reuse existing codebase (low build cost)

The graduation project already has file/attachment infrastructure:

| Existing asset | Deliverable use |
|----------------|-----------------|
| Spatie Media Library (`Post`, `Event`, `Club`, `Committee`) | `Task` implements `HasMedia` — one file in `deliverable` collection |
| `ClubResource` + file upload patterns | Reference for upload UX and storage |
| `SyncsImageUploads` concern | Adapt for task file sync on complete |
| Volunteer hour **approval** workflow | Pattern for Review → Approve → Done |
| `TaskStatus::Review` (already in schema plan) | Status enum already accounts for review gate |

Connect `Task` → Spatie media and deliverables are **mostly wiring**, not a new file system.

#### Future (v2 — do not build in MVP)

```
Task
└── Deliverables (has many)
    ├── v1.pdf
    ├── v2.pdf
    └── final.pdf
```

Multi-version history, multiple attachments per task, and deliverable templates belong in Phase 9 backlog.

#### Why this belongs in v1

- Immediately answers *"What was actually produced?"* for NGOs, agencies, and program managers
- Differentiates from personal task trackers and simple Kanban clones
- `review` status + deliverable creates a lightweight audit trail without building full document management
- Strong portfolio story: real teamwork semantics, not checkbox theater

---

## 9. Gap analysis

### Already have ✅

| Need | Existing asset |
|------|----------------|
| User accounts | Fortify auth |
| Organizations | `Club` model |
| Sub-teams | `Committee` model |
| Members + invites | `ClubMembership`, join applications |
| Permissions | `ClubCapability`, `CommitteeCapability`, Gates, policies |
| Deadlines (loose) | `Event` with `starts_at` / `ends_at` |
| Approval workflows | Volunteer hours, join applications |
| Activity publishing | `Post` (news) |
| File sharing | `ClubResource` + Spatie Media |
| Task file attachments | Spatie `HasMedia` on `Post`, `Event`, `Club` — reuse on `Task` |
| Approval workflows | Volunteer hours, join applications → **Review → Approve** pattern for deliverables |
| Personal dashboard | Student dashboard pattern |
| Manager dashboard | Club/committee management controllers |
| AI mutations | `WriteTool` confirm-before-write pattern |
| Admin back office | Filament |
| i18n | `lang/ar`, `lang/en` |
| Tests | Pest feature + unit tests |

### Must build 🆕

| Need | New work |
|------|----------|
| Work items | `Task` model (title, description, status, priority, due_date) |
| Assignment | `assignee_id`, optional multiple assignees later |
| Project container | `Project` model or repurpose `Committee` |
| Task views | List view (v1), Kanban board (v2) |
| **Task deliverables** | Upload file / paste link / notes on complete; store on `Task` + Spatie media |
| **Review workflow** | `review` status; lead approves deliverable → `done` |
| Task comments | `TaskComment` model + UI |
| Activity log | Task/project audit trail |
| Notifications | Email + in-app (task assigned, due soon, mention) |
| My work view | Filtered task list per user |
| Project overview | Progress summary, overdue count, member workload |
| AI task tools | Create/list/update tasks via assistant |

### Optional / later 🔜

| Feature | Notes |
|---------|-------|
| Kanban drag-and-drop | High UI effort; defer to v2 |
| Subtasks | `parent_id` on tasks |
| Labels/tags | Reuse `HasTags` concern |
| Time tracking | Repurpose `VolunteerHour` → `TimeEntry` |
| Milestones | Slim `Event` or new `Milestone` model |
| Dependencies | `blocked_by` relationships |
| Completion certificates | Repurpose certificate designer |
| Public project pages | Repurpose club public pages |

---

## 10. Conceptual model mapping

### Option A — Rename in place (faster, messier)

| Current (Ruwad) | New (Team Hub) |
|-----------------|----------------|
| University | *(remove or → Tenant root)* |
| Club | Workspace |
| Committee | Project |
| ClubMembership | WorkspaceMember |
| Event | Milestone / Deadline |
| Post | ProjectUpdate |
| ClubResource | ProjectFile |
| VolunteerHour | TimeEntry |
| Certificate | CompletionDoc *(optional)* |
| Student | Member |
| Supervisor | ProjectLead |

### Option B — New models alongside (cleaner, more migration work)

Keep `Club`/`Committee` temporarily; introduce parallel `Workspace`/`Project`/`Task` domain; migrate data; deprecate old names.

**Recommendation:** Option A for MVP speed if starting a **new repo fork** with no production data. Option B if evolving Ruwad in place with existing users.

### Domain diagram (target state)

```
Workspace (org)
├── Members (roles: owner, admin, member)
├── Projects
│   ├── Members (inherited or project-specific)
│   ├── Tasks
│   │   ├── Status (todo | in_progress | review | done)
│   │   ├── Assignee
│   │   ├── Due date, priority
│   │   ├── Deliverable (file | link | notes) — submitted on complete
│   │   └── Comments
│   ├── Milestones (optional)
│   ├── Updates (feed)
│   └── Files
└── Settings (branding, invites)
```

### Permission mapping

| Capability (new) | Inspired by |
|------------------|-------------|
| `manage_workspace` | `ClubCapability::ManageClub` |
| `manage_project` | `CommitteeCapability::ManageCommittee` |
| `manage_tasks` | `ClubCapability::ManageEvents` (closest analog) |
| `assign_tasks` | New |
| `view_reports` | `ClubCapability::ViewReports` |
| `manage_members` | `ClubCapability::ManageMembers` |

---

## 11. Detailed implementation plan

### Overview timeline

| Phase | Duration | Goal |
|-------|----------|------|
| **Phase 0** — Decision & setup | 1 week | Brand, repo, strip uni domain |
| **Phase 1** — Domain foundation | 2 weeks | Models, migrations, policies |
| **Phase 2** — Core task CRUD + deliverables | 2 weeks | Create, edit, assign, status, submit deliverable, review |
| **Phase 3** — Project & workspace UI | 2 weeks | Navigation, project pages, member mgmt |
| **Phase 4** — My work & dashboards | 1 week | Personal task views |
| **Phase 5** — Comments & activity | 1 week | Collaboration basics |
| **Phase 6** — AI assistant tools | 1 week | Task-aware assistant |
| **Phase 7** — Polish, i18n, tests | 2 weeks | Arabic copy, Pest coverage, UX |
| **Phase 8** — Deploy & pilot | 1 week | Staging, 1–2 pilot teams |

**Total: 10–14 weeks** (1–2 developers, part-time adjust accordingly)

---

### Phase 0 — Decision & setup (Week 1)

**Goals:** Clean slate, new identity, no university leakage.

#### Tasks

- [ ] **0.1** Choose product name, domain, and positioning statement
- [ ] **0.2** Fork repo → new GitHub repo (or new branch `pivot/team-hub`)
- [ ] **0.3** Add root `LICENSE` file (MIT) with correct copyright holders
- [ ] **0.4** Replace branding assets:
  - `lang/en/seo.php`, `lang/ar/seo.php`
  - `lang/en/app.php`, `lang/ar/app.php`
  - Auth layout (`resources/js/layouts/auth/RuwadAuthLayout.svelte` → rename)
  - CSS animation prefixes (`ruwad-*` → product prefix)
- [ ] **0.5** Remove or feature-flag university-specific code:
  - `University` model (if not needed)
  - UQU copy in `lang/en/clubs.php`, `lang/en/events.php`, etc.
  - Demo seeders referencing university staff
- [ ] **0.6** Update `.env.example` (`MAIL_FROM_ADDRESS`, `APP_NAME`)
- [ ] **0.7** Write internal glossary (Club→Workspace, etc.) for team alignment
- [ ] **0.8** Set up project board for Phases 1–8

#### Deliverables

- New repo/branch with neutral branding
- Glossary doc (can live in this file's mapping section)
- Empty task backlog structured by phase

#### Exit criteria

- `composer test` passes
- No "Umm Al-Qura" or "Ruwad" in user-facing strings (or behind feature flag)
- Team agrees on Option A vs B model strategy

---

### Phase 1 — Domain foundation (Weeks 2–3)

**Goals:** Database schema and authorization for workspaces, projects, tasks.

#### 1.1 Database migrations

**`workspaces` table** (or rename `clubs`):

```
id, name, slug, theme, logo (media), settings (json), created_at, updated_at, deleted_at
```

**`projects` table** (or rename `committees`):

```
id, workspace_id, name, slug, description, status (active|archived), created_at, updated_at, deleted_at
```

**`tasks` table** (new):

```
id
project_id (FK, indexed)
created_by (FK users)
assignee_id (FK users, nullable, indexed)
title
description (text, nullable)
status (enum: todo, in_progress, review, done) — indexed
priority (enum: low, normal, high, urgent, nullable)
due_date (date, nullable, indexed)
position (integer, for ordering within status column)
deliverable_url (string, nullable)
deliverable_notes (text, nullable)
deliverable_submitted_at (timestamp, nullable)
deliverable_submitted_by (FK users, nullable)
completed_at (timestamp, nullable)
timestamps
soft_deletes (optional)
```

**Media:** `Task` implements `HasMedia` with collection `deliverable` (single file in v1).

**`task_comments` table** (new):

```
id, task_id, user_id, body (text), timestamps
```

**Indexes:** `(project_id, status)`, `(assignee_id, status)`, `(due_date)` where not null.

#### 1.2 Eloquent models

- [ ] `Workspace` (or aliased `Club` with renamed relations)
- [ ] `Project` (or aliased `Committee`)
- [ ] `Task` with scopes: `overdue()`, `dueToday()`, `forAssignee()`, `byStatus()`, `inReview()`
- [ ] `Task` implements `HasMedia` — `deliverable` collection (single file)
- [ ] `TaskComment`
- [ ] Factories for all new models
- [ ] Seeders: 1 workspace, 2 projects, 20 tasks, 3 users with different roles

#### 1.3 Enums

- [ ] `TaskStatus` (todo, in_progress, review, done)
- [ ] `TaskPriority` (low, normal, high, urgent)
- [ ] `ProjectStatus` (active, archived)
- [ ] `WorkspaceCapability` / `ProjectCapability` (or extend existing enums)

#### 1.4 Authorization

- [ ] `TaskPolicy`: view (project member), create/update (manage_tasks or assignee for own), delete (manage_tasks)
- [ ] `ProjectPolicy`: extend committee policy pattern
- [ ] Register Gates in `AppServiceProvider` (mirror club/committee pattern)
- [ ] Form requests: `StoreTaskRequest`, `UpdateTaskRequest` with auth in `authorize()`

#### 1.5 API / routes (web)

```
GET    /workspaces/{workspace}/projects/{project}/tasks
POST   /workspaces/{workspace}/projects/{project}/tasks
GET    /tasks/{task}
PATCH  /tasks/{task}
POST   /tasks/{task}/deliverable     # submit file/link/notes → status: review
POST   /tasks/{task}/approve         # project lead → status: done
POST   /tasks/{task}/request-changes # project lead → status: in_progress
DELETE /tasks/{task}
POST   /tasks/{task}/comments
GET    /my-tasks
```

Use Wayfinder generation after route registration.

#### Deliverables

- Migrations run clean on fresh DB
- Policy tests for task access (member vs outsider vs admin)
- Model unit tests for scopes

#### Exit criteria

- Can create tasks via `php artisan tinker` with correct policy enforcement
- Pest: `TaskPolicyTest`, `TaskModelTest` green

---

### Phase 2 — Core task CRUD (Weeks 4–5)

**Goals:** Users can create, edit, assign, change task status, and **submit deliverables for review** from the UI.

#### 2.1 Backend

- [ ] `TaskController` — index (by project), store, show, update, destroy
- [ ] `TaskCommentController` — store (index can be eager-loaded on task show)
- [ ] `MyTasksController` — all tasks assigned to current user across projects
- [ ] Inertia props: task list with assignee, creator, comment count, overdue flag
- [ ] Validation: title required, due_date optional, assignee must be project member

#### 2.2 Frontend — Svelte pages

- [ ] `resources/js/pages/projects/Tasks/Index.svelte` — list grouped by status or flat with filters
- [ ] `resources/js/pages/projects/Tasks/Show.svelte` — task detail + inline edit
- [ ] `resources/js/components/tasks/TaskForm.svelte` — shared create/edit form
- [ ] `resources/js/components/tasks/TaskRow.svelte` — list item with status badge, assignee avatar, due date
- [ ] `resources/js/components/tasks/StatusSelect.svelte` — quick status change
- [ ] `resources/js/components/tasks/PriorityBadge.svelte`

#### 2.3 UX behaviors (v1)

- [ ] Filter by: status, assignee, priority, overdue
- [ ] Sort by: due date, created date, priority
- [ ] Empty states with CTA to create first task
- [ ] Optimistic or flash-based feedback on status change

#### 2.4 Task completion & deliverables

**Goals:** Completing a task captures *what was produced* and moves work into review — not just a checkbox.

##### Backend

- [ ] `SubmitTaskDeliverableRequest` — validate at least one of: file, `deliverable_url`, `deliverable_notes` (when required)
- [ ] `TaskDeliverableController@store` — attach media, set `deliverable_*` fields, `status → review`, log activity
- [ ] `TaskReviewController@approve` — `status → done`, set `completed_at` (lead/manager only)
- [ ] `TaskReviewController@requestChanges` — `status → in_progress`, optional comment
- [ ] Policy: assignee (or `manage_tasks`) can submit deliverable; `manage_project` or assignee's lead can approve

##### Frontend

- [ ] `CompleteTaskModal.svelte` — triggered from status change or "Submit for review" action
  - Upload file (reuse existing file input / Spatie upload pattern)
  - Paste link field
  - Notes textarea
  - Submit → task moves to Review
- [ ] `TaskDeliverableCard.svelte` — on task detail: show file download, link, notes, submitter, timestamp
- [ ] `ReviewActions.svelte` — Approve / Request changes (visible to project lead only)
- [ ] Status badge distinguishes `review` from `done`

##### UX rules (v1)

- [ ] Moving to `done` directly (skip review) allowed only for users with `manage_tasks` — otherwise must go through `review`
- [ ] Task list shows deliverable indicator (paperclip / link icon) on completed tasks
- [ ] Empty deliverable allowed when project setting `deliverable_required = false` (default: optional for MVP)

##### Translations

- [ ] `lang/en/tasks.php`, `lang/ar/tasks.php` — deliverable labels, modal copy, review actions, validation messages

#### 2.5 Translations (general)

- [ ] `lang/en/tasks.php`, `lang/ar/tasks.php` — statuses, priorities, empty states, validation
- [ ] `lang/en/projects.php`, `lang/ar/projects.php`

#### Deliverables

- Working task list and detail per project
- Create task modal or page
- Assign to any project member
- **Complete-task modal** with file upload, link, and notes
- **Review workflow:** submit deliverable → approve or request changes

#### Exit criteria

- Manual test: create project → add 3 members → create 10 tasks → assign → submit deliverable → lead approves → task shows deliverable on detail
- Feature tests: `TaskCrudTest.php` (auth boundaries), `TaskDeliverableTest.php` (submit + approve flow)

---

### Phase 3 — Project & workspace UI (Weeks 6–7)

**Goals:** Navigation shell, project overview, member management.

#### 3.1 Workspace level

- [ ] Workspace switcher (if user belongs to multiple) — adapt club nav
- [ ] Workspace settings: name, logo, theme color
- [ ] Member list: invite, role change, remove (reuse membership controllers)
- [ ] Workspace home: project cards, recent activity summary

#### 3.2 Project level

- [ ] Project overview page:
  - Task counts by status
  - Overdue count
  - Members strip
  - Recent updates (stub or wired to news)
- [ ] Project settings: name, description, archive
- [ ] Project sidebar nav: Overview | Tasks | Files | Updates

#### 3.3 Repurpose existing UI

| Existing | Repurpose to |
|----------|--------------|
| `ClubManageNavItem.svelte` | `ProjectNavItem.svelte` |
| Committee management layout | Project management layout |
| Club public page (optional) | Workspace landing (optional v2) |

#### 3.4 Filament (staff admin)

- [ ] `TaskResource` for support/debug (read-only or full CRUD)
- [ ] `ProjectResource` if not already covered by committee resource rename

#### Deliverables

- Coherent nav: Workspace → Projects → Tasks
- Project overview with live task stats

#### Exit criteria

- New user can be invited → join workspace → open project → see task list
- `ProjectOverviewTest.php` asserts stats props

---

### Phase 4 — My work & dashboards (Week 8)

**Goals:** Every member has a personal command center.

#### 4.1 My Tasks page

- [ ] Sections: Overdue | Due today | Upcoming | No due date
- [ ] Quick complete / status change from list
- [ ] Link through to project context
- [ ] Replace or extend `student-dashboard` → `member-dashboard`

#### 4.2 Manager view (optional v1.5)

- [ ] Project lead sees team workload: tasks per assignee
- [ ] Reuse report service patterns from `GetClubReport` AI tool

#### 4.3 Homepage (logged in)

- [ ] Redirect or widget: "You have X overdue tasks"
- [ ] Recent project activity

#### Deliverables

- `/my-tasks` page fully functional
- Dashboard shows actionable task summary

#### Exit criteria

- User with tasks in 3 projects sees unified my-tasks view
- Feature test: `MyTasksTest.php`

---

### Phase 5 — Comments & activity (Week 9)

**Goals:** Basic collaboration on tasks.

#### 5.1 Comments

- [ ] Comment form on task detail
- [ ] Comment list with author + timestamp
- [ ] Delete own comment (or admin delete)

#### 5.2 Activity log (lightweight)

- [ ] `task_activities` table OR polymorphic `activity_log`:
  - `task.created`, `task.status_changed`, `task.assigned`, `task.deliverable_submitted`, `task.deliverable_approved`, `task.changes_requested`, `comment.added`
- [ ] Activity feed on task detail (chronological)
- [ ] Project-level activity rollup (last 10 events)

#### 5.3 Notifications (minimal v1)

- [ ] Laravel notification: `TaskAssigned`
- [ ] Laravel notification: `TaskSubmittedForReview` (notify project lead)
- [ ] Laravel notification: `TaskDeliverableApproved` / `TaskChangesRequested` (notify assignee)
- [ ] Email on assign (configurable later)
- [ ] In-app notification table (optional; can defer to email-only v1)

#### Deliverables

- Comment thread on tasks
- Activity visible on task page

#### Exit criteria

- Assign task → assignee receives email
- Comment appears in task activity

---

### Phase 6 — AI assistant tools (Week 10)

**Goals:** Task-aware AI using existing assistant infrastructure.

#### 6.1 New read tools

- [ ] `ListMyTasks` — overdue, due today, by project
- [ ] `GetProjectSummary` — task counts, blockers
- [ ] `FindTasks` — search by title, assignee, status

#### 6.2 New write tools (extend `WriteTool`)

- [ ] `CreateTask` — preview → confirm → execute
- [ ] `UpdateTaskStatus` — preview → confirm
- [ ] `AssignTask` — preview → confirm

#### 6.3 Assistant instructions

- [ ] Update `Assistant::instructions()` for project terminology
- [ ] Remove club/event-specific tools or gate behind feature flag
- [ ] Register tools based on `ProjectCapability` / membership

#### 6.4 Frontend

- [ ] Update suggestion chips on dashboard for task queries
- [ ] Confirm cards for task mutations (reuse existing confirm UI)

#### Deliverables

- "What tasks are overdue?" works in assistant
- "Create a task for Ahmed due Friday" flows through confirm card

#### Exit criteria

- Pest tests for each new tool (auth + happy path)
- Manual Arabic prompt test

---

### Phase 7 — Polish, i18n, tests (Weeks 11–12)

**Goals:** Production-quality v1.

#### 7.1 Arabic / RTL

- [ ] All new strings in `en` + `ar`
- [ ] RTL test task list, forms, modals
- [ ] Date formatting locale-aware

#### 7.2 UX polish

- [ ] Loading skeletons on task list
- [ ] Error boundaries / friendly 403 page
- [ ] Keyboard: Enter to submit comment, Esc to close modal
- [ ] Mobile-responsive task list (no Kanban yet)

#### 7.3 Testing

- [ ] Feature tests: full flows (workspace → project → task → comment)
- [ ] Policy tests for every capability
- [ ] AI tool tests
- [ ] `composer ci:check` green

#### 7.4 Documentation

- [ ] Update main `README.md` for new product
- [ ] User-facing help: how to create project, assign tasks
- [ ] Admin runbook: env vars, deploy

#### 7.5 Performance

- [ ] Eager load assignee, comment count on task index
- [ ] Index DB columns used in filters
- [ ] Octane smoke test

#### Deliverables

- CI green
- Bilingual UI complete
- README reflects new product

#### Exit criteria

- `composer ci:check` passes
- Manual RTL walkthrough recorded or signed off

---

### Phase 8 — Deploy & pilot (Week 13)

**Goals:** Real users, real feedback.

#### 8.1 Infrastructure

- [ ] Staging environment (reuse Coolify / nixpacks setup)
- [ ] Production environment
- [ ] Redis for queue + cache
- [ ] Email provider configured (assign notifications)

#### 8.2 Pilot program

- [ ] Recruit 1–2 pilot teams (5–15 users each)
- [ ] Onboarding call: create workspace, first project, first 10 tasks
- [ ] Feedback form after 2 weeks

#### 8.3 Metrics to collect

- Tasks created per week
- % tasks with assignee + due date
- DAU / WAU
- Assistant usage count
- Drop-off point in onboarding

#### Deliverables

- Live staging + production URLs
- Pilot feedback doc

#### Exit criteria

- At least one pilot team using product for 2 consecutive weeks
- No P0 bugs open

---

### Phase 9 — v2 backlog (post-MVP)

Prioritize based on pilot feedback.

| Feature | Effort | Value |
|---------|--------|-------|
| Kanban board (drag-and-drop) | High | High |
| Subtasks | Medium | High |
| Labels / tags on tasks | Low | Medium |
| Multiple deliverable versions per task | Medium | High |
| Deliverable required/optional per project | Low | Medium |
| Milestones linked to tasks | Medium | Medium |
| Time tracking | Medium | Medium (NGO angle) |
| @mentions in comments | Medium | Medium |
| In-app notifications center | Medium | High |
| Project templates | Medium | Medium |
| Calendar view (due dates) | Medium | Medium |
| Export / PDF reports | Low | Medium (Arabic PDF advantage) |
| Workspace-level AI insights | High | High |
| Public project pages | Medium | Low (unless marketplace) |
| Mobile PWA | Medium | Medium |
| Integrations (Slack, email-in) | High | Depends on users |

---

## 12. What to keep, repurpose, and drop

### Keep as-is

- Laravel + Inertia + Svelte stack
- Fortify authentication
- Scoped Gates / capabilities pattern
- Filament admin
- Wayfinder typed routes
- AI assistant architecture (`WriteTool` confirm flow)
- Pest testing setup
- Octane deployment
- Tailwind + bits-ui components
- i18n structure (`lang/ar`, `lang/en`)

### Repurpose

| Asset | New use |
|-------|---------|
| Club | Workspace |
| Committee | Project |
| ClubMembership | Workspace membership |
| Post | Project update / announcement |
| ClubResource | Project files |
| Event | Milestone (slim down) |
| VolunteerHour | Time entry (optional) |
| Student dashboard | Member dashboard |
| Join application flow | Workspace invite request |
| Report services | Project progress reports |
| Certificate designer | Completion certificate (optional) |

### Drop or feature-flag (for team hub v1)

- QR attendance scanner
- Event registration + capacity
- University model and staff role (replace with workspace owner)
- Public club discovery catalog (unless needed)
- Certificate issuance (unless pilot asks)
- Demo university seeders

---

## 13. Validation and success metrics

### Pre-build validation (before Phase 1)

- [ ] 5 interviews with target users (NGO lead, startup PM, freelancer team lead)
- [ ] Confirm top 3 pain points: task visibility, assignment clarity, Arabic UI
- [ ] Confirm willingness to switch from WhatsApp + spreadsheets

### MVP success (8 weeks post-launch)

| Metric | Target |
|--------|--------|
| Pilot teams | ≥ 2 |
| Weekly active users | ≥ 20 |
| Tasks created / week | ≥ 100 (across pilots) |
| Task completion rate | ≥ 40% of created tasks reach `done` |
| Assignee set on create | ≥ 70% of tasks |
| Due date set on create | ≥ 50% of tasks |
| Tasks with deliverable on complete | ≥ 50% of tasks reaching `review` or `done` |
| NPS from pilots | ≥ 30 |

### Kill criteria (pivot again if)

- No pilot completes onboarding after 4 weeks outreach
- Users create tasks but never return (WAU/MAU < 20%)
- Feedback consistently says "just use Notion/Trello"

---

## 14. Risks and mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep (building full Asana) | Delays MVP 3–6 months | Strict v1 scope doc; Phase 9 backlog only |
| Kanban UI complexity | Blocks launch | List view only in v1 |
| Renaming vs new models confusion | Tech debt | Choose Option A or B in Phase 0; stick to it |
| University IP dispute | Legal | Confirm ownership before commercial launch |
| Missing `LICENSE` file | Fork ambiguity | Add LICENSE in Phase 0 |
| Notification fatigue | Users ignore emails | Batch digests in v2; in-app prefs |
| AI tool misuse | Wrong task created | Keep confirm-before-write for all mutations |
| RTL bugs in new components | Bad Arabic UX | RTL review in Phase 7 gate |
| Performance on large task lists | Slow projects | Pagination + indexes from day one |

---

## 15. Decision log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-06 | Codebase is MIT-licensed | `composer.json` + README |
| 2026-06 | Do not pursue generic uni apps as next product | Saturated; branding too UQU-specific |
| 2026-06 | Project tracking is viable on this base | Strong RBAC + dashboard + AI; tasks are new but bounded |
| 2026-06 | Niche: Arabic-first team hub, not global Asana clone | Defensible wedge |
| 2026-06 | MVP = list view, no Kanban | Ship faster; Kanban is v2 |
| 2026-06 | Task deliverables in v1 (file + link + notes) | Answers "what was produced?"; reuse Spatie media + review status |
| 2026-06 | Four statuses including `review` | Matches real team handoff: submit → review → approve → done |
| TBD | Option A (rename) vs Option B (new models) | Decide in Phase 0 based on fork vs in-place |
| TBD | Product name and domain | Marketing decision |
| 2026-06 | **Fork** to new repo `Alkabkabi1/TeamHUB` | Clean git history; break from Ruwad remote |
| 2026-06 | **Option A phased** (identity now, model renames later) | Avoid messy big-bang rename; tests stay green per wave |
| 2026-06 | **Product name: TeamHUB** | `APP_NAME=TeamHUB`, public repo TeamHUB |

---

## 16. Appendix: tech stack reference

From the current Ruwad codebase:

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13 (PHP 8.4) |
| Runtime | Laravel Octane + RoadRunner |
| Frontend | Inertia.js v3 + Svelte 5 |
| Styling | Tailwind CSS v4, bits-ui |
| Admin panel | Filament v4 (Livewire) |
| Auth | Laravel Fortify |
| Typed routes | Laravel Wayfinder |
| AI | Laravel AI SDK (`Laravel\Ai\`) |
| Media | Spatie Media Library |
| PDF | DomPDF (Arabic shaping via `App\Support\ArabicText`) |
| Testing | Pest v4 |
| Tooling | Vite 8, ESLint 9, Prettier 3, Pint |

### Key directories (current)

```
app/
  Ai/Tools/          AI assistant tools
  Filament/          Staff admin panel
  Http/Controllers/  Inertia + web controllers
  Models/            Eloquent models
  Services/          Domain services
  Support/           Helpers (ArabicText)
resources/js/pages/  Svelte pages (Inertia)
lang/                ar/ and en/ translations
database/            migrations, factories, seeders
tests/               Pest feature & unit tests
```

### Dev commands

```bash
composer setup    # install, .env, migrate, build
composer dev      # server + queue + logs + Vite
composer test     # lint + Pest
composer ci:check # full CI gate
```

---

## Next actions

1. **Decide:** Fork new repo vs pivot in place.
2. **Decide:** Product name and first pilot customer profile.
3. **Execute Phase 0** (branding strip + LICENSE file).
4. **Schedule** 5 user interviews before writing task migrations.
5. **Create** GitHub issues from Phase 1–8 checklists.

---

*This document captures planning discussions on platform reuse, licensing, alternative product ideas, and the recommended pivot to project tracking & teamwork. Update the decision log as choices are made.*
