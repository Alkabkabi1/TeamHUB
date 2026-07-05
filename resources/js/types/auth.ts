export type ManagedWorkspace = {
    id: number;
    name: string;
    logo_url: string | null;
};

export type ManagedProject = {
    id: number;
    name: string;
    workspace_id: number;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role?: string;
    is_workspace_lead?: boolean;
    managed_workspaces?: ManagedWorkspace[];
    is_project_lead?: boolean;
    managed_projects?: ManagedProject[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
