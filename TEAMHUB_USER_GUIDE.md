# TeamHUB User Guide

## Core concepts

- `Workspace`: the top-level team container. In the current codebase this maps to the existing `Club` model.
- `Project`: a delivery team inside a workspace. In the current codebase this maps to the existing `Committee` model.
- `Task`: the unit of work. Tasks can have an assignee, due date, priority, deliverable, review state, comments, and activity history.

## Main flow

### 0. Open Team Hub (primary dashboard)

After login, most users land on **`/hub/dashboard`** — the unified Team Hub shell with projects, tasks, calendar, notifications, and workspace navigation.

- `/hub/dashboard` — overview KPIs, today's tasks, activity feed
- `/hub/projects` — all projects (committees) you can access
- `/hub/tasks` — cross-project task list with search and status filters

Use the sidebar **demo role switcher** (when enabled) to preview the app as another seeded user without logging out manually.

### 1. Open your workspace or project

- Use the main navigation to open a workspace dashboard or a project management page.
- Project managers can jump directly to the project task list from the project shell.
- Individual members can open `My Tasks` to see assigned work across every project they belong to.

### 2. Create and assign a task

- Go to a project task page.
- Fill in the task title, optional description, assignee, priority, status, and due date.
- Save the task. Managers can return later to edit metadata or reassign the task.

### 3. Work on the task

- Assignees use `My Tasks` or the project task page to open the task detail view.
- Move the task between `Todo` and `In progress` as needed.
- Add comments for context, blockers, and handoff notes.

### 4. Submit a deliverable

- On the task detail page, attach at least one of the following:
  - a file upload
  - a link
  - delivery notes
- Submit the task for review.
- TeamHUB records the submission in task activity and notifies the relevant reviewers.

### 5. Review and approve

- Project leads open tasks in `Review`.
- Add review notes, then either:
  - approve and complete the task
  - request changes and send it back to `In progress`

### 6. Use the AI assistant

- Open the assistant from the TeamHUB UI.
- Current supported flows are task-first:
  - list your tasks
  - find tasks by title, assignee, status, or priority
  - summarize a project
  - create a task
  - assign a task
  - update task details
  - update task status or review outcome
- Write actions use a confirm-before-save flow.

## Helpful pages

- `My Tasks`: cross-project personal task dashboard
- `Notifications`: unread alerts for assignments, comments, and review actions
- Project task detail: deliverables, comments, activity log, and review tools

## Notes

- Appearance mode now persists across visits.
- TeamHUB is bilingual and supports both Arabic (`rtl`) and English (`ltr`) surfaces.
- If a page is denied, ask a workspace or project lead to confirm your membership or management role.
