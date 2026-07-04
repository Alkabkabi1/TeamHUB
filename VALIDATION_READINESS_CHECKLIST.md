# Validation Readiness Checklist

Use this checklist before starting Phase 6.

## Goal
Confirm that TeamHUB satisfies the README and roadmap objectives through a mix of automated checks and manual smoke testing.

## Automated gate
Run the full local quality gate:

```bash
composer ci:check
```

This should cover:
- PHP formatting check
- ESLint check
- Prettier check
- Svelte type check
- PHP test suite

## Preview route smoke test
These routes should render without auth and reflect the TeamHUB design preview:
- `/preview/team-hub`
- `/preview/team-hub/dashboard`
- `/preview/team-hub/tasks`
- `/preview/team-hub/projects`
- `/preview/team-hub/deliverable`

Expected result:
- each page returns successfully
- layout and branding load
- dashboard/tasks/projects/deliverable preview pages are reachable

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

## Objective mapping
These checks map back to the README and roadmap objectives:
- workspaces/projects: workspace and project shell checks
- tasks and assignment: task lifecycle checks
- deliverables and review: deliverable submission/review checks
- personal dashboard and my-tasks: personal work surface checks
- comments, activity, notifications: collaboration and notifications checks
- project files and updates: project resources and updates checks

## Known non-blocking follow-up
These remain tracked for polish rather than readiness:
- dark mode persistence
- theme/brand reset issues on some page transitions
- remaining old-branding or legacy copy cleanup

See `PHASE_7_POLISH_NOTES.md`.
