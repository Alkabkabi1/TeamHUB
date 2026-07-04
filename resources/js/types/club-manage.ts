export type ClubManageMember = {
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

export type ClubManagePendingMember = {
    id: number;
    name: string;
    details: string;
    time: string;
};

export type ClubManageEvent = {
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

export type ClubManageRoleOption = {
    value: string;
    label: string;
    isManager: boolean;
};

export type ClubManageFoundUser = {
    id: number;
    name: string;
    email: string;
};
