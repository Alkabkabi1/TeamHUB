export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type DashboardTask = {
    id: number;
    title: string;
    project: string;
    priority: TaskPriority;
    dueDate: string;
    dueLabel: string;
    status: TaskStatus;
    assignee: { name: string; initials: string };
    url?: string;
    project_id?: number;
    workspace_id?: number;
};

export type DashboardProject = {
    id: number;
    workspace_id: number;
    title: string;
    description: string;
    progress: number;
    tasksCount: number;
    membersCount: number;
    color: string;
    icon: string;
    members: string[];
    url: string;
    manage_url?: string;
};

export type DashboardActivity = {
    id: number;
    user: string;
    initials: string;
    action: string;
    target: string;
    time: string;
    type: 'comment' | 'complete' | 'upload' | 'assign';
};

export type DashboardKpi = {
    id: string;
    label: string;
    value: number;
    trend: string;
    trendUp: boolean;
    icon: 'projects' | 'overdue' | 'progress' | 'done';
};

export type AppNavItem = {
    href: string;
    label: string;
    icon: string;
    badge?: number | null;
};

export type SidebarWorkspace = {
    id: number;
    name: string;
    letter: string;
    color: string;
    url: string;
};

export type CreatableWorkspace = {
    id: number;
    name: string;
    create_url: string;
};

export type CalendarMarker = {
    date: string;
    title: string;
    type: 'task' | 'event';
};

export type RoleContext = {
    panel: 'member' | 'project_lead' | 'workspace_lead' | 'admin';
    review_count: number;
    assigned_count: number;
};
