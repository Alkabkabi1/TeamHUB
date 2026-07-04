import type { TaskPriority, TaskStatus } from '@/types/team-hub';

export const statusLabels: Record<TaskStatus, string> = {
    todo: 'للتنفيذ',
    in_progress: 'قيد التنفيذ',
    review: 'مراجعة',
    done: 'مكتمل',
};

export const priorityLabels: Record<TaskPriority, string> = {
    low: 'منخفضة',
    medium: 'متوسطة',
    high: 'عالية',
    urgent: 'عاجلة',
};
