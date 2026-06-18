export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type Task = {
    id: number;
    title: string;
    project: string;
    priority: TaskPriority;
    dueDate: string;
    dueLabel: string;
    status: TaskStatus;
    assignee: { name: string; initials: string };
};

export type Project = {
    id: number;
    title: string;
    description: string;
    progress: number;
    tasksCount: number;
    membersCount: number;
    color: string;
    icon: string;
    members: string[];
};

export type Activity = {
    id: number;
    user: string;
    initials: string;
    action: string;
    target: string;
    time: string;
    type: 'comment' | 'complete' | 'upload' | 'assign';
};

export type Kpi = {
    id: string;
    label: string;
    value: number;
    trend: string;
    trendUp: boolean;
    icon: 'projects' | 'overdue' | 'progress' | 'done';
};

export const navItems = [
    { href: '/preview/team-hub/dashboard', label: 'الرئيسية', icon: 'home' as const },
    { href: '/preview/team-hub/projects', label: 'المشاريع', icon: 'projects' as const },
    { href: '/preview/team-hub/tasks', label: 'المهام', icon: 'tasks' as const },
    { href: '/preview/team-hub/deliverable', label: 'تسليم المخرجات', icon: 'deliverable' as const },
    { href: '#', label: 'فريقي', icon: 'team' as const },
    { href: '#', label: 'التقويم', icon: 'calendar' as const },
    { href: '#', label: 'الملفات', icon: 'files' as const },
    { href: '#', label: 'التقارير', icon: 'reports' as const },
    { href: '#', label: 'الإشعارات', icon: 'notifications' as const, badge: 3 },
];

export const workspaces = [
    { name: 'فريق التطوير', color: '#8b5cf6', letter: 'ت' },
    { name: 'التسويق', color: '#22c55e', letter: 'س' },
    { name: 'تصميم المنتج', color: '#3b82f6', letter: 'ص' },
    { name: 'البحث', color: '#a855f7', letter: 'ب' },
];

export const kpis: Kpi[] = [
    { id: 'projects', label: 'مشاريع نشطة', value: 8, trend: '+14%', trendUp: true, icon: 'projects' },
    { id: 'overdue', label: 'مهام متأخرة', value: 6, trend: '+3%', trendUp: false, icon: 'overdue' },
    { id: 'progress', label: 'مهام قيد التنفيذ', value: 18, trend: '-5%', trendUp: false, icon: 'progress' },
    { id: 'done', label: 'مهام مكتملة', value: 24, trend: '+12%', trendUp: true, icon: 'done' },
];

export const projects: Project[] = [
    {
        id: 1,
        title: 'تطوير منصة الفريق',
        description: 'بناء لوحة تحكم للمشاريع والمهام',
        progress: 70,
        tasksCount: 24,
        membersCount: 6,
        color: '#8b5cf6',
        icon: 'monitor',
        members: ['أ', 'م', 'س', 'ن'],
    },
    {
        id: 2,
        title: 'تطبيق الجوال',
        description: 'واجهة المستخدم والتكامل مع API',
        progress: 40,
        tasksCount: 18,
        membersCount: 4,
        color: '#3b82f6',
        icon: 'mobile',
        members: ['خ', 'ر', 'ل'],
    },
    {
        id: 3,
        title: 'موقع الشركة',
        description: 'إعادة تصميم الصفحة الرئيسية',
        progress: 60,
        tasksCount: 12,
        membersCount: 3,
        color: '#22c55e',
        icon: 'web',
        members: ['ف', 'ه', 'و'],
    },
    {
        id: 4,
        title: 'حملة تسويقية',
        description: 'إطلاق المنتج الجديد',
        progress: 25,
        tasksCount: 9,
        membersCount: 5,
        color: '#c8924a',
        icon: 'megaphone',
        members: ['ز', 'ي', 'ع'],
    },
];

export const tasks: Task[] = [
    {
        id: 1,
        title: 'تصميم واجهة لوحة التحكم',
        project: 'تطوير منصة الفريق',
        priority: 'high',
        dueDate: '2026-06-10',
        dueLabel: 'غداً',
        status: 'in_progress',
        assignee: { name: 'أحمد', initials: 'أ' },
    },
    {
        id: 2,
        title: 'مراجعة متطلبات API',
        project: 'تطبيق الجوال',
        priority: 'medium',
        dueDate: '2026-06-12',
        dueLabel: '12 يونيو',
        status: 'review',
        assignee: { name: 'سارة', initials: 'س' },
    },
    {
        id: 3,
        title: 'كتابة محتوى الصفحة الرئيسية',
        project: 'موقع الشركة',
        priority: 'low',
        dueDate: '2026-06-15',
        dueLabel: '15 يونيو',
        status: 'todo',
        assignee: { name: 'محمد', initials: 'م' },
    },
    {
        id: 4,
        title: 'إعداد حملة البريد الإلكتروني',
        project: 'حملة تسويقية',
        priority: 'urgent',
        dueDate: '2026-06-09',
        dueLabel: 'اليوم',
        status: 'in_progress',
        assignee: { name: 'نورة', initials: 'ن' },
    },
    {
        id: 5,
        title: 'اختبار تدفق تسليم المخرجات',
        project: 'تطوير منصة الفريق',
        priority: 'high',
        dueDate: '2026-06-11',
        dueLabel: '11 يونيو',
        status: 'review',
        assignee: { name: 'خالد', initials: 'خ' },
    },
];

export const lateTasks = [
    { id: 1, title: 'تحديث وثائق المشروع', daysLate: 2 },
    { id: 2, title: 'مراجعة التصميم النهائي', daysLate: 5 },
    { id: 3, title: 'إرسال تقرير الأسبوع', daysLate: 1 },
];

export const activities: Activity[] = [
    {
        id: 1,
        user: 'سارة العتيبي',
        initials: 'س',
        action: 'علّقت على',
        target: 'تصميم الواجهة',
        time: 'منذ 15 دقيقة',
        type: 'comment',
    },
    {
        id: 2,
        user: 'أحمد محمد',
        initials: 'أ',
        action: 'أكمل مهمة',
        target: 'إعداد قاعدة البيانات',
        time: 'منذ ساعة',
        type: 'complete',
    },
    {
        id: 3,
        user: 'محمد خالد',
        initials: 'م',
        action: 'رفع ملف',
        target: 'المتطلبات الجديدة.pdf',
        time: 'منذ ساعتين',
        type: 'upload',
    },
    {
        id: 4,
        user: 'نورة',
        initials: 'ن',
        action: 'طلبت مراجعة',
        target: 'حملة البريد الإلكتروني',
        time: 'منذ 3 ساعات',
        type: 'assign',
    },
];

export const statusLabels: Record<TaskStatus, string> = {
    todo: 'قيد الانتظار',
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
