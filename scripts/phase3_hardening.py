#!/usr/bin/env python3
"""Bulk contract canonicalization for Phase 3."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

PHP_DIRS = ["app", "tests", "lang", "database/factories", "routes", "bootstrap"]
JS_DIRS = ["resources/js"]

# Order matters: longer / more specific patterns first.
REPLACEMENTS: list[tuple[str, str]] = [
    # Service methods
    ("clubMembersForManagement", "workspaceMembersForManagement"),
    ("committeeMembersForManagement", "projectMembersForManagement"),
    ("clubStats", "workspaceStats"),
    ("committeeStats", "projectStats"),
    # Controller constants
    ("COMMITTEE_SORTS", "PROJECT_SORTS"),
    # Validation / error bag keys
    ("errors()->add('club'", "errors()->add('workspace'"),
    ("assertSessionHasErrors('club'", "assertSessionHasErrors('workspace'"),
    ("assertSessionHasErrors(['club'", "assertSessionHasErrors(['workspace'"),
    # Translation keys
    ("__('join.validation.club.", "__('join.validation.workspace."),
    ("join.validation.club.", "join.validation.workspace."),
    ("__('project.members.must_be_club_member')", "__('project.members.must_be_workspace_member')"),
    ("__('project.members.validation.not_club_member')", "__('project.members.validation.not_workspace_member')"),
    ("__('committee_roles.", "__('project_roles."),
    ("__('club_roles.", "__('workspace_roles."),
    ("__('club.", "__('workspace."),
    ("__('clubs.", "__('workspace."),
    ("__('committees.", "__('project."),
    ('__("club.', '__("workspace.'),
    ('__("clubs.', '__("workspace.'),
    ('__("committees.', '__("project.'),
    ("dashboard_supervisor", "dashboard_workspace_lead"),
    # Notification interpolation keys
    ("['club' =>", "['workspace' =>"),
    ("['committee' =>", "['project' =>"),
    ("'club_name'", "'workspace_name'"),
    ("'committee_name'", "'project_name'"),
    # Inertia payload keys (array literal)
    ("'clubs' =>", "'workspaces' =>"),
    ("'committees' =>", "'projects' =>"),
    ("'club' =>", "'workspace' =>"),
    ("'committee' =>", "'project' =>"),
    # Payload access
    ("$payload['club']", "$payload['workspace']"),
    # Test inertia assertions
    ("->has('clubs'", "->has('workspaces'"),
    ("->has('committees'", "->has('projects'"),
    ("->has('club'", "->has('workspace'"),
    ("->has('committee'", "->has('project'"),
    ("->where('clubs.", "->where('workspaces."),
    ("->where('club.", "->where('workspace."),
    ("->where('committee.", "->where('project."),
    # PDF filenames
    ("-club-{$workspace", "-workspace-{$workspace"),
    ("-committee-{$project", "-project-{$project"),
    # Filament
    ("CheckboxList::make('club_roles')", "CheckboxList::make('workspace_roles')"),
    ("['club_roles']", "['workspace_roles']"),
    ("'club_roles'", "'workspace_roles'"),
    # Test helpers
    ("supervisorForClub(", "supervisorForWorkspace("),
    ("->clubSupervisor()", "->workspaceSupervisor()"),
    ("committeeMembership(", "projectMembership("),
    # Factory
    ("function clubSupervisor", "function workspaceSupervisor"),
    # Permissions in comments/strings
    ("manage-club", "manage-workspace"),
    ("manage-committee-news", "manage-project-updates"),
    ("manage-committee-members", "manage-project-members"),
    ("view-committee-reports", "view-project-reports"),
    ("manage-committee", "manage-project"),
    # Private method names
    ("committeeLeader(", "projectLeader("),
    ("function committeeLeader", "function projectLeader"),
]

JS_REPLACEMENTS: list[tuple[str, str]] = [
    ("committee_roles.", "project_roles."),
    ("club_roles.", "workspace_roles."),
    ("manage-committee-news", "manage-project-updates"),
    ("manage-committee-members", "manage-project-members"),
    ("view-committee-reports", "view-project-reports"),
    ("manage-committee", "manage-project"),
    ("manage-club", "manage-workspace"),
    ("clubId", "workspaceId"),
    ("committee_name", "project_name"),
    ("club_name", "workspace_name"),
    ("errors.club", "errors.workspace"),
    ("club={workspace}", "workspace={workspace}"),
    ("/committees`", "/projects`"),
    ("task.club", "task.workspace"),
    ("task.committee", "task.project"),
    ("update.club_name", "update.workspace_name"),
    ("update.committee_name", "update.project_name"),
    # Prop types in $props destructuring - handled by regex below
]

# Regex replacements for Svelte prop contracts
SVELTE_REGEX: list[tuple[str, str]] = [
    (r"\bclub:\s*workspace,?\n", ""),
    (r"\bcommittee:\s*project(?:\s*=\s*null)?,?\n", ""),
    (r"\bcommittees:\s*projects\s*=\s*\[\],?\n", ""),
    (r"\bclubs:\s*workspaces\s*=\s*\[\],?\n", ""),
    (r"\bclub:\s*Workspace", "workspace: Workspace"),
    (r"\bclub:\s*WorkspaceRef", "workspace: WorkspaceRef"),
    (r"\bclub:\s*WorkspaceBranding", "workspace: WorkspaceBranding"),
    (r"\bclub:\s*ManageWorkspace", "workspace: ManageWorkspace"),
    (r"\bcommittee:\s*ProjectRef", "project: ProjectRef"),
    (r"\bcommittee:\s*ProjectSummary", "project: ProjectSummary"),
    (r"\bcommittee:\s*ProjectListItem", "project: ProjectListItem"),
    (r"\bcommittee\?:\s*ProjectSummary", "project?: ProjectSummary"),
    (r"\bcommittee\?:\s*WorkspaceRef", "project?: WorkspaceRef"),
    (r"\bcommittees\?:\s*ProjectListItem", "projects?: ProjectListItem"),
    (r"\bcommittee:\s*Project;", "project: Project;"),
    (r"\bcommittee:\s*\{", "project: {"),
    (r"\bclub:\s*WorkspaceRef\s*&", "workspace: WorkspaceRef &"),
    (r"CAP\.club\b", "CAP.workspace"),
    (r"\bcommittee:\s*'manage-project'", "project: 'manage-project'"),
    (r"\bclub:\s*'manage-workspace'", "workspace: 'manage-workspace'"),
    (r"\bclub\?:\s*string", "workspace?: string"),
    (r"\bclub\s*=\s*null", "workspace = null"),
    (r"\{#if club\}", "{#if workspace}"),
    (r"\{club\}", "{workspace}"),
    (r"clubName:", "workspaceName:"),
    (r"\bclubName\b", "workspaceName"),
]


def should_process_php(path: Path) -> bool:
    if path.suffix != ".php":
        return False
    parts = set(path.parts)
    return "vendor" not in parts and "node_modules" not in parts


def should_process_js(path: Path) -> bool:
    if path.suffix not in {".svelte", ".ts"}:
        return False
    parts = set(path.parts)
    return "vendor" not in parts and "node_modules" not in parts


def apply_replacements(content: str, replacements: list[tuple[str, str]]) -> str:
    for old, new in replacements:
        content = content.replace(old, new)
    return content


def process_php_files() -> int:
    changed = 0
    for dir_name in PHP_DIRS:
        base = ROOT / dir_name
        if not base.exists():
            continue
        for path in base.rglob("*.php"):
            if not should_process_php(path):
                continue
            original = path.read_text(encoding="utf-8")
            updated = apply_replacements(original, REPLACEMENTS)
            if updated != original:
                path.write_text(updated, encoding="utf-8")
                changed += 1
    return changed


def process_js_files() -> int:
    changed = 0
    for dir_name in JS_DIRS:
        base = ROOT / dir_name
        if not base.exists():
            continue
        for path in base.rglob("*"):
            if not should_process_js(path):
                continue
            original = path.read_text(encoding="utf-8")
            updated = apply_replacements(original, JS_REPLACEMENTS)
            for pattern, repl in SVELTE_REGEX:
                updated = re.sub(pattern, repl, updated)
            if updated != original:
                path.write_text(updated, encoding="utf-8")
                changed += 1
    return changed


def main() -> None:
    php_changed = process_php_files()
    js_changed = process_js_files()
    print(f"Updated {php_changed} PHP files and {js_changed} JS files.")


if __name__ == "__main__":
    main()
