# Validation Readiness Checklist

> **Superseded:** This document is retained for historical reference. For post-deploy verification on staging or production, use **[PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md)** instead.

---

## Historical note

The preview route smoke tests below referenced `/preview/team-hub/*` routes that were removed during Phase 3.5 repository cleanup. Do not use those steps.

---

## Automated gate

Run the full local quality gate:

```bash
composer ci:check
composer analyse
```

## Core workflow checklist

Use one workspace, one project, one project lead, and one project member.

### Workspace and project shell

- Open the workspace management area
- Open a project from the workspace/project shell
- Reach the project task list through the project shell
- Open the project settings page
- Confirm the project switcher appears when the user manages multiple projects

### Task lifecycle

- Create a new task inside a project
- Assign it to an approved project member
- Confirm the assignee can open the task detail page
- Move the task from `todo` to `in_progress`
- Submit a deliverable using file, link, or notes
- Confirm the lead can review the task
- Approve the deliverable and confirm the task reaches `done`

### Collaboration

- Add a comment on the task as the assignee
- Add a comment as the project lead
- Confirm comments appear on the task detail page with author and timestamp
- Confirm task activity shows task creation, assignment, progress change, deliverable submission, comment events, and approval

### Personal work surfaces

- Open `/student-dashboard`
- Confirm actionable task summary appears
- Open `/my-tasks`
- Confirm assigned tasks are grouped correctly
- After approval, confirm the completed task no longer appears as open work

### Notifications

- Confirm assignment creates an unread notification for the assignee
- Confirm deliverable submission creates an unread notification for the project lead
- Confirm approval or requested changes creates an unread notification for the assignee
- Open `/notifications`
- Confirm notifications can be marked individually as read
- Confirm `Mark all as read` clears unread notifications

### Project resources and updates

- Open the project `Files` section
- Open the project `Updates` section
- Confirm the project overview shows recent updates and recent task activity

## Known non-blocking follow-up

See `PHASE_7_POLISH_NOTES.md` for deferred polish items (dark mode, theme consistency).
