# TeamHUB User Guide

## Core concepts

- **Workspace** — the top-level organization container (your team, NGO, or program).
- **Project** — a delivery team inside a workspace (e.g. a campaign, product squad, or committee).
- **Task** — a unit of work with an assignee, due date, priority, deliverable, review state, comments, and activity history.

## Main flow

### 0. After login

Most members land on the **student dashboard** or **workspace overview**, depending on role.

- `/my-tasks` — cross-project task list for work assigned to you
- `/notifications` — unread alerts for assignments, comments, and reviews
- `/workspaces/{workspace}` — workspace home (news, projects, members)
- `/workspaces/{workspace}/projects/{project}` — project overview

When `DEMO_QUICK_LOGIN=true` (local only), the home page offers quick demo logins to preview different roles.

### 1. Open your workspace or project

- Use the sidebar to open a workspace or project you belong to.
- Project leads can open **Manage** from the project page to configure members, files, and updates.
- Members use **My Tasks** to see assigned work across every project.

### 2. Create and assign a task

- Go to a project's task list (`/workspaces/{workspace}/projects/{project}/tasks`).
- Create a task with title, optional description, assignee, priority, status, and due date.
- Project leads can edit metadata or reassign later.

### 3. Work on the task

- Open the task from **My Tasks** or the project task list.
- Move between **Todo** and **In progress** as you work.
- Add comments for context, blockers, and handoff notes (press **Enter** to submit; **Esc** blurs the field).

### 4. Submit a deliverable

On the task detail page, attach at least one of:

- a file upload
- a link (Figma, Drive, GitHub PR, etc.)
- delivery notes

Submit for review. TeamHUB records the submission in the activity log and notifies reviewers.

### 5. Review and approve

Project leads open tasks in **Review**, add review notes, then either:

- **Approve** — task moves to **Done**
- **Request changes** — task returns to **In progress**

### 6. Use the AI assistant

Open the assistant from the TeamHUB shell. Supported task-first flows:

- list your tasks
- find tasks by title, assignee, status, or priority
- summarize a project
- create, assign, or update a task
- update task status or review outcome

Write actions use a **confirm-before-save** flow.

## Helpful pages

| Page | Purpose |
|------|---------|
| My Tasks | Personal dashboard across all projects |
| Notifications | Unread assignment, comment, and review alerts |
| Task detail | Deliverables, comments, activity, review tools |
| Workspace manage | Members, join requests, reports (leads) |
| Project manage | Members, files, updates (project leads) |

## Language and appearance

- TeamHUB is bilingual: Arabic (`rtl`) and English (`ltr`). Switch locale from user settings.
- Light and dark mode persist across visits (stored in the browser).

## Access issues

If a page returns **403 Forbidden**, ask a workspace or project lead to confirm your membership and role.

## For operators

Deploy and day-2 operations: see [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md), [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md), and [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md).
