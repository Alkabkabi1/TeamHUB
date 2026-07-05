#!/usr/bin/env python3
"""Bulk text replacements for Phase 1 hardening."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DIRS = ["app", "tests", "database", "routes", "bootstrap", "lang"]

# Order matters: longer / more specific patterns first.
REPLACEMENTS: list[tuple[str, str]] = [
    # Namespaces & classes
    ("App\\Http\\Controllers\\TeamHub\\TeamHubDashboardController", "App\\Http\\Controllers\\DashboardController"),
    ("App\\Http\\Controllers\\TeamHub\\TeamHubProjectsController", "App\\Http\\Controllers\\ProjectsOverviewController"),
    ("App\\Http\\Controllers\\TeamHub\\TeamHubTasksController", "App\\Http\\Controllers\\TasksOverviewController"),
    ("App\\Http\\Controllers\\TeamHub\\TeamHubTaskController", "App\\Http\\Controllers\\TaskDetailController"),
    ("App\\Http\\Controllers\\TeamHub\\TeamHubActionController", "App\\Http\\Controllers\\DashboardActionController"),
    ("namespace App\\Http\\Controllers\\TeamHub;", "namespace App\\Http\\Controllers;"),
    ("App\\Support\\TeamHub\\TeamHubDashboardPresenter", "App\\Support\\DashboardPresenter"),
    ("App\\Support\\TeamHub\\TeamHubData", "App\\Support\\DashboardData"),
    ("App\\Support\\TeamHub\\TeamHubNav", "App\\Support\\AppNav"),
    ("App\\Support\\TeamHub\\ProjectPresenter", "App\\Support\\ProjectPresenter"),
    ("App\\Support\\TeamHub\\TaskPresenter", "App\\Support\\TaskPresenter"),
    ("namespace App\\Support\\TeamHub;", "namespace App\\Support;"),
    ("App\\Concerns\\AuthorizesClubOrCommittee", "App\\Concerns\\AuthorizesWorkspaceOrProject"),
    ("App\\Http\\Middleware\\EnsureUniversityStaff", "App\\Http\\Middleware\\EnsureAdmin"),
    ("App\\Services\\ClubSupervisorReportService", "App\\Services\\WorkspaceMemberReportService"),
    ("App\\Services\\CommitteeReportService", "App\\Services\\ProjectMemberReportService"),
    ("DownloadClubReportRequest", "DownloadWorkspaceReportRequest"),
    ("DownloadCommitteeReportRequest", "DownloadProjectReportRequest"),
    ("UpdateClubThemeRequest", "UpdateWorkspaceThemeRequest"),
    ("AuthorizesClubOrCommittee", "AuthorizesWorkspaceOrProject"),
    ("EnsureUniversityStaff", "EnsureAdmin"),
    ("ClubSupervisorReportService", "WorkspaceMemberReportService"),
    ("CommitteeReportService", "ProjectMemberReportService"),
    ("TeamHubDashboardPresenter", "DashboardPresenter"),
    ("TeamHubDashboardController", "DashboardController"),
    ("TeamHubProjectsController", "ProjectsOverviewController"),
    ("TeamHubTasksController", "TasksOverviewController"),
    ("TeamHubTaskController", "TaskDetailController"),
    ("TeamHubActionController", "DashboardActionController"),
    ("TeamHubData", "DashboardData"),
    ("TeamHubNav", "AppNav"),
    ("ClubJoinApplicationController", "WorkspaceMembershipRequestController"),
    ("ClubManagementController", "WorkspaceManagementController"),
    ("ClubMemberController", "WorkspaceMemberController"),
    ("ClubReportController", "WorkspaceReportController"),
    ("ClubThemeController", "WorkspaceThemeController"),
    ("CommitteeManagementController", "ProjectManagementController"),
    ("CommitteeMemberController", "ProjectMemberController"),
    ("CommitteeMembershipController", "ProjectMembershipController"),
    ("CommitteeReportController", "ProjectReportController"),
    ("CommitteeResourceController", "ProjectFileController"),
    ("NewsController", "ProjectUpdateController"),
    ("ClubController", "WorkspaceController"),
    ("CommitteeController", "ProjectController"),
    # Filament (namespace only — avoid touching App\Models\ClubResource)
    ("App\\Filament\\Resources\\Clubs\\", "App\\Filament\\Resources\\Workspaces\\"),
    ("Filament\\Resources\\Workspaces\\ClubResource", "Filament\\Resources\\Workspaces\\WorkspaceResource"),
    ("class ClubResource extends Resource", "class WorkspaceResource extends Resource"),
    ("Pages\\CreateClub", "Pages\\CreateWorkspace"),
    ("Pages\\EditClub", "Pages\\EditWorkspace"),
    ("Pages\\ListClubs", "Pages\\ListWorkspaces"),
    ("Schemas\\ClubForm", "Schemas\\WorkspaceForm"),
    ("Tables\\ClubsTable", "Tables\\WorkspacesTable"),
    ("RelationManagers\\PostsRelationManager", "RelationManagers\\ProjectUpdatesRelationManager"),
    ("RelationManagers\\ResourcesRelationManager", "RelationManagers\\ProjectFilesRelationManager"),
    ("class PostsRelationManager", "class ProjectUpdatesRelationManager"),
    ("class ResourcesRelationManager", "class ProjectFilesRelationManager"),
    ("class CreateClub ", "class CreateWorkspace "),
    ("class EditClub ", "class EditWorkspace "),
    ("class ListClubs ", "class ListWorkspaces "),
    ("class ClubForm ", "class WorkspaceForm "),
    ("class ClubsTable ", "class WorkspacesTable "),
    # ResourcesRelationManager only in Workspaces context - careful with ClubResource model
    # AI tools
    ("ApplyToClub", "RequestWorkspaceMembership"),
    ("ApproveClubApplication", "ApproveWorkspaceMembershipRequest"),
    ("RejectClubApplication", "RejectWorkspaceMembershipRequest"),
    ("AddClubMember", "AddWorkspaceMember"),
    ("RemoveClubMember", "RemoveWorkspaceMember"),
    ("GetMyClubs", "GetMyWorkspaces"),
    ("FindClubs", "FindWorkspaces"),
    ("GetClubInfo", "GetWorkspaceInfo"),
    ("GetClubMembers", "GetWorkspaceMembers"),
    ("GetClubReport", "GetWorkspaceReport"),
    ("GetClubPendingApplications", "GetWorkspacePendingApplications"),
    ("ApplyToCommittee", "RequestProjectMembership"),
    ("ApproveCommitteeApplication", "ApproveProjectMembershipRequest"),
    ("RejectCommitteeApplication", "RejectProjectMembershipRequest"),
    ("AddCommitteeMember", "AddProjectMember"),
    ("RemoveCommitteeMember", "RemoveProjectMember"),
    ("FindCommittees", "FindProjects"),
    ("GetMyCommittees", "GetMyProjects"),
    ("GetCommitteeInfo", "GetProjectInfo"),
    ("GetCommitteeReport", "GetProjectReport"),
    ("FindResources", "FindProjectFiles"),
    ("SearchCatalog", "SearchCatalog_REMOVED"),
    # Route names
    ("route('hub.dashboard'", "route('dashboard'"),
    ('route("hub.dashboard"', 'route("dashboard"'),
    ("route('hub.projects'", "route('projects'"),
    ("route('hub.tasks.show'", "route('tasks.show'"),
    ("route('hub.tasks'", "route('tasks'"),
    ("route('hub.admin.projects.store'", "route('dashboard.projects.store'"),
    ("route('hub.admin.assign-leader'", "route('dashboard.assign-leader'"),
    ("route('hub.admin.message-leader'", "route('dashboard.message-leader'"),
    ("route('hub.leader.tasks.store'", "route('dashboard.tasks.store'"),
    ("route('hub.leader.tasks.approve'", "route('tasks.approve'"),
    ("route('hub.leader.tasks.request-changes'", "route('tasks.request-changes'"),
    ("route('hub.staff.deliverable'", "route('tasks.deliverable'"),
    ("route('clubs.manage'", "route('workspaces.manage'"),
    ("route('clubs.show'", "route('workspaces.show'"),
    ("route('clubs.join'", "route('workspaces.join'"),
    ("route('clubs.members'", "route('workspaces.members'"),
    ("route('clubs.reports'", "route('workspaces.reports'"),
    ("route('clubs.theme'", "route('workspaces.theme'"),
    ("route('clubs'", "route('workspaces'"),
    ("route('committees.manage'", "route('projects.manage'"),
    ("route('committees.show'", "route('projects.show'"),
    ("route('committees.index'", "route('projects.index'"),
    ("route('committees.tasks'", "route('projects.tasks'"),
    ("route('committees.members'", "route('projects.members'"),
    ("route('committees.files'", "route('projects.files'"),
    ("route('committees.updates'", "route('projects.updates'"),
    ("route('committees.news'", "route('projects.updates'"),
    ("route('committees.join'", "route('projects.join'"),
    ("route('committees.memberships'", "route('projects.memberships'"),
    ("route('committees.reports'", "route('projects.reports'"),
    ("route('committees.create'", "route('projects.create'"),
    ("route('committees.edit'", "route('projects.edit'"),
    ("route('committees.store'", "route('projects.store'"),
    ("route('committees.update'", "route('projects.update'"),
    ("route('committees.destroy'", "route('projects.destroy'"),
    ("route('join-applications.approve'", "route('workspaces.membership-requests.approve'"),
    ("route('join-applications.reject'", "route('workspaces.membership-requests.reject'"),
    ("route('news.destroy'", "route('projects.updates.destroy'"),
    # Translation keys
    ("__('hub.", "__('dashboard."),
    ('__("hub.', '__("dashboard.'),
    ("__('club_roles.", "__('workspace_roles."),
    ("__('committee_roles.", "__('project_roles."),
    ("__('club.", "__('workspace."),
    ("__('clubs.", "__('workspace."),
    ("__('committees.", "__('project."),
    ("__('committee_members.", "__('project.members."),
    # User / demo
    ("club-leader@teamhub.test", "workspace-lead@teamhub.test"),
    ("committee-leader@teamhub.test", "project-lead@teamhub.test"),
    ("'hub.dashboard'", "'dashboard'"),
    ("isUniversityStaff", "isAdmin"),
    # Param route segments in strings
    ("{club}", "{workspace}"),
    ("{committee}", "{project}"),
    # DemoWorkspace
    ("defaultClub()", "defaultWorkspace()"),
    ("DemoWorkspace::defaultClub", "DemoWorkspace::defaultWorkspace"),
]

CLASS_RENAMES_IN_FILES: dict[str, str] = {
    "WorkspaceController.php": "class WorkspaceController",
    "WorkspaceMembershipRequestController.php": "class WorkspaceMembershipRequestController",
    "WorkspaceManagementController.php": "class WorkspaceManagementController",
    "WorkspaceMemberController.php": "class WorkspaceMemberController",
    "WorkspaceReportController.php": "class WorkspaceReportController",
    "WorkspaceThemeController.php": "class WorkspaceThemeController",
    "ProjectController.php": "class ProjectController",
    "ProjectManagementController.php": "class ProjectManagementController",
    "ProjectMemberController.php": "class ProjectMemberController",
    "ProjectMembershipController.php": "class ProjectMembershipController",
    "ProjectReportController.php": "class ProjectReportController",
    "ProjectFileController.php": "class ProjectFileController",
    "ProjectUpdateController.php": "class ProjectUpdateController",
    "DashboardController.php": "class DashboardController",
    "ProjectsOverviewController.php": "class ProjectsOverviewController",
    "TasksOverviewController.php": "class TasksOverviewController",
    "TaskDetailController.php": "class TaskDetailController",
    "DashboardActionController.php": "class DashboardActionController",
    "EnsureAdmin.php": "class EnsureAdmin",
    "WorkspaceMemberReportService.php": "class WorkspaceMemberReportService",
    "ProjectMemberReportService.php": "class ProjectMemberReportService",
    "DownloadWorkspaceReportRequest.php": "class DownloadWorkspaceReportRequest",
    "DownloadProjectReportRequest.php": "class DownloadProjectReportRequest",
    "UpdateWorkspaceThemeRequest.php": "class UpdateWorkspaceThemeRequest",
    "AuthorizesWorkspaceOrProject.php": "trait AuthorizesWorkspaceOrProject",
    "DashboardPresenter.php": "class DashboardPresenter",
    "DashboardData.php": "class DashboardData",
    "AppNav.php": "class AppNav",
    "WorkspaceResource.php": "class WorkspaceResource",
}


def should_process(path: Path) -> bool:
    if path.suffix != ".php":
        return False
    parts = set(path.parts)
    if "vendor" in parts or "node_modules" in parts:
        return False
    return True


def apply_replacements(content: str) -> str:
    for old, new in REPLACEMENTS:
        content = content.replace(old, new)
    return content


def fix_controller_class(content: str, filename: str) -> str:
    target = CLASS_RENAMES_IN_FILES.get(filename)
    if not target:
        return content
    class_name = target.split()[-1]
    return re.sub(r"class\s+\w+", f"class {class_name}", content, count=1)


def main() -> None:
    changed = 0
    for dir_name in DIRS:
        base = ROOT / dir_name
        if not base.exists():
            continue
        for path in base.rglob("*.php"):
            if not should_process(path):
                continue
            original = path.read_text(encoding="utf-8")
            updated = apply_replacements(original)
            updated = fix_controller_class(updated, path.name)
            if updated != original:
                path.write_text(updated, encoding="utf-8")
                changed += 1
    print(f"Updated {changed} files")


if __name__ == "__main__":
    main()
