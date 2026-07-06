import type { Workspace, WorkspaceBranding, WorkspaceRef } from './models';

/** Minimal project reference for navigation and labels. */
export type ProjectRef = {
    id: number;
    name: string;
    logo_url?: string | null;
    status?: string;
};

/** Project with catalog/detail fields shown on project pages. */
export type ProjectSummary = ProjectRef & {
    description: string | null;
    theme: string | null;
    image_url: string | null;
    status: string;
};

/** Project card in workspace project listings. */
export type ProjectListItem = {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    members_count: number;
    tasks_count: number;
};

/** Workspace context on manage screens. */
export type WorkspaceManageContext = WorkspaceBranding & {
    university?: string | null;
};

export type { Workspace, WorkspaceBranding, WorkspaceRef };
