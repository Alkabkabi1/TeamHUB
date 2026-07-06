# TeamHUB — Product Vision

**Status:** Phase 0 (product definition)  
**Version target:** 1.0.0 after re-engineering  
**Audience:** Engineers, designers, and evaluators working on TeamHUB

---

## Non-negotiable rule

Every feature in TeamHUB must answer this question:

> **Would this still make sense in a generic company, startup, open-source team, or community organization?**

| Answer | Action |
|--------|--------|
| **No** | Remove it |
| **Yes** | Generalize it (remove university-only assumptions) |
| **Unclear** | Redesign or defer — never keep solely because it existed in the original university codebase |

---

## What TeamHUB is

TeamHUB is a **standalone, Arabic-first platform for team and project work**.

It is **not** a university club system, **not** a Ruwad fork in product terms, and **not** a legacy adaptation with renamed labels. Phases 1–3.5 completed the domain, UI, and runtime migration to the target vocabulary below.

### One-line pitch

**Arabic-first teamwork where completing a task means submitting real output and getting lead approval — not checking a box.**

### The problem

Traditional task tools (Trello, Asana, Notion boards) optimize for *status*:

- Todo → Done
- Attachments are optional and disconnected
- Review is informal (comments, DMs, meetings)
- Arabic and RTL are often second-class

Teams — especially Arabic-speaking NGOs, startups, and program groups — need **accountability for output**: designs, documents, links, files, and explicit sign-off.

### The solution

TeamHUB centers the **deliverable review loop**:

1. Work is organized in **workspaces** (organizations) and **projects** (focused teams).
2. **Tasks** are assigned with clear ownership and due dates.
3. Completing work requires a **deliverable** (file, link, notes).
4. The task enters **review**; a project lead **approves** or **requests changes**.
5. **Comments**, **activity**, and **notifications** keep everyone aligned.
6. An **AI assistant** helps with tasks but **confirms before any write**.

---

## Who it is for

| Audience | Fit |
|----------|-----|
| NGOs and community organizations | Strong |
| Arabic-speaking startups and small companies | Strong |
| Program / project teams inside larger orgs | Strong |
| Open-source and volunteer coordination teams | Strong |
| University student clubs as primary persona | **Out of scope** |

---

## Who it is not for (v1.0)

- Public university club catalogs and academic reporting
- Event attendance, certificates, and volunteer-hour compliance
- Enterprise PM replacement (Gantt, automations, 100+ integrations)
- Multi-tenant SaaS billing and self-serve org provisioning (future roadmap)

---

## Product principles

### 1. Standalone product

TeamHUB is shipped and explained as its own product. Attribution to upstream open source belongs in `NOTICE` — not in the user-facing story.

### 2. No university compatibility

We do **not** maintain parallel “university mode” or feature flags to preserve club/academic workflows. University-specific concepts are removed or redesigned, not hidden.

### 3. Breaking changes are allowed

Renaming domain models, routes, database tables, and UI flows is expected during re-engineering. Data migration scripts may be required; backward compatibility with pre-1.0 URLs is not a goal.

### 4. Reuse code only when it fits

Inherited code from the prior codebase is kept only if it naturally supports generic team workflows. When in doubt, apply the non-negotiable rule above.

### 5. Remove university-specific concepts

Examples to eliminate from the product (not merely rename):

- Universities as tenants, colleges, academic majors in join forms
- Public club discovery catalogs
- Attendance QR, events, certificates, volunteer hours
- “Student club” terminology in UI and docs

### 6. Arabic-first

- Default locale: Arabic
- RTL layout as a first-class experience
- Notifications and emails respect recipient locale

### 7. Safe AI

- Read tools scoped to the authenticated user’s workspaces and projects
- Write tools require authorization and explicit user confirmation
- AI mutations are logged in task activity where applicable

---

## Differentiation vs traditional task tools

| Traditional PM | TeamHUB |
|----------------|---------|
| Done = checkbox | Done = **approved deliverable** |
| Review in side channels | **In Review** status + approve / request changes |
| Files optional | Deliverable is part of the workflow |
| English-first | **Arabic-first**, bilingual |
| AI edits immediately | **Confirm before write** |

---

## Core capabilities (target v1.0)

| Capability | Description |
|------------|-------------|
| Workspaces | Organization container; members and settings |
| Projects | Teams within a workspace; tasks, files, updates |
| Tasks | Assignable work with priority, status, due dates |
| Deliverables | Files, links, notes; multi-file support (target) |
| Review workflow | Extended lifecycle including changes requested |
| Comments & mentions | Collaboration on tasks |
| Activity feed | Audit trail on tasks and projects |
| Files | Project-level file library |
| Membership requests | Request to join workspace (generalized, non-academic) |
| Notifications | Assignment, comments, deliverable events, mentions |
| Reports (PDF) | Project, task, team, and deliverable summaries (generalized) |
| AI assistant | Task/project scoped tools |

See [DOMAIN_MODEL.md](./DOMAIN_MODEL.md) for entity relationships.

---

## Success criteria (product)

After re-engineering, a new user should:

1. Understand TeamHUB as a **team work platform**, not a school system
2. Complete **project → task → deliverable → review → done** without leaving one app shell
3. Never encounter “club”, “committee”, “university”, or “volunteer hours” in the UI

---

## رؤية المنتج (عربي)

**TeamHUB** منصة عربية لإدارة فرق العمل تركز على **التسليم والمراجعة** لا على مجرّد إغلاق المهمة.

**لمن؟** جمعيات، شركات ناشئة، فرق برامج، مجتمعات تقنية — وليس كنظام نوادي جامعية.

**المبدأ:** أي ميزة يجب أن تكون منطقية لشركة أو فريق عام. إن كانت خاصة بالجامعة فقط — تُحذف أو تُعاد تصميمها.

---

## Related documents

| Document | Purpose |
|----------|---------|
| [DOMAIN_MODEL.md](./DOMAIN_MODEL.md) | Entities and relationships (target architecture) |
| [ENGINEERING_PRINCIPLES.md](./ENGINEERING_PRINCIPLES.md) | Re-engineering rules for implementers |
| [../README.md](../README.md) | Setup and stack (updated in Phase 9) |

**Implementation checklist:** see the TeamHUB Re-engineering plan (Phases 1–10). Phase 0 stops here — no code changes.
