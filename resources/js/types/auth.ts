export type ManagedClub = {
    id: number;
    name: string;
    logo_url: string | null;
};

export type ManagedCommittee = {
    id: number;
    name: string;
    club_id: number;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role?: string;
    is_club_supervisor?: boolean;
    managed_clubs?: ManagedClub[];
    is_committee_leader?: boolean;
    managed_committees?: ManagedCommittee[];
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
