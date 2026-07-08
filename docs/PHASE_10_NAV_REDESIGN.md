# TeamHUB Phase 10 — Navigation & Role Simplification

**Status:** Complete  
**Goal:** One clear home per role, canonical task routes only, and policies that match the product rules.

---

## Role → home map

| Role | Home | Primary actions |
|------|------|-----------------|
| Platform admin (مدير) | `/dashboard` | Create projects, assign project leads, metrics; Filament `/admin` |
| Workspace lead | `/dashboard` (workspace panel) + `/workspaces/{w}/manage` | Workspace settings, members, create projects, assign project leads |
| Project lead (قائد المشروع) | `/dashboard?project={id}` | Assign tasks, review deliverables; deep link to project task board |
| Member (موظف) | `/my-tasks` | Execute assigned work, submit deliverables |

---

## Route redirects (legacy bookmarks)

| Legacy URL | Redirect |
|------------|----------|
| `/student-dashboard` | `/my-tasks` |
| `/projects` | `/dashboard` |
| `/tasks` | `/my-tasks` (members) or `/dashboard` (leads/admins) |
| `/tasks/{task}` | `/workspaces/{w}/projects/{p}/tasks/{t}` |
| `/workspaces/{w}` | `/workspaces/{w}/manage` (managers) or `/dashboard` (members) |

---

## Authorization changes

- **Project capabilities** come only from approved `ProjectMembership` roles (e.g. `ProjectLead`).
- Platform admins and workspace leads **no longer inherit** all project capabilities on every project in scope.
- Task **create / approve / delete** require `ProjectCapability::ManageProject`.
- Removed dashboard-level task mutation routes (`dashboard.tasks.store`, `tasks.approve`, etc.); use scoped `projects.tasks.*` endpoints.

---

## UI changes

- Role-aware sidebar via [`AppNav.php`](../app/Support/AppNav.php) (≤4 items per role).
- New [`WorkspaceLeadDashboardPanel`](../resources/js/components/app/dashboard/WorkspaceLeadDashboardPanel.svelte).
- Staff demo persona and plain members redirect to **My Tasks** instead of a duplicate dashboard.
- Single notification entry in sidebar (removed duplicate bell on dashboard header).
- Removed pages: `WorkspacePage`, `StudentDashboard`, `app/Tasks`, `app/Projects`, `app/TaskShow`.

---

## Success criteria

- [x] Members land on `/my-tasks`; leads and admins land on `/dashboard`.
- [x] Admins and workspace leads cannot assign/approve tasks via policy without project lead membership.
- [x] Legacy URLs redirect to canonical destinations.
- [x] `composer ci:check` passes.
