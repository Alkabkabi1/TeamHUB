export type WorkspaceManageMember = {
    membershipId: number;
    userId: number;
    name: string;
    email: string;
    major: string;
    joinDate: string;
    volunteerHours: number;
    roles: string[];
    isManager: boolean;
    status: string;
};

export type WorkspaceManagePendingMember = {
    id: number;
    name: string;
    details: string;
    time: string;
};

export type WorkspaceManageEvent = {
    id: number;
    title: string;
    starts_at: string | null;
    ends_at: string | null;
    location: string | null;
    capacity: number | null;
    status: string;
    attendances_count: number;
    scannable: boolean;
};

export type WorkspaceManageRoleOption = {
    value: string;
    label: string;
    isManager: boolean;
};

export type WorkspaceManageFoundUser = {
    id: number;
    name: string;
    email: string;
};
