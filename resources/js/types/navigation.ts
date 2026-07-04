import type { IconSvgElement } from '@hugeicons/svelte';
import type { LinkComponentBaseProps } from '@inertiajs/core';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
    icon?: IconSvgElement;
    badge?: number;
    isActive?: boolean;
    roles?: string[];
    isLogout?: boolean;
    isExternal?: boolean;
    // Marks a special-rendered entry (e.g. the club-management dashboard, which
    // is a single link or a dropdown depending on how many clubs the user manages).
    kind?: 'club-manage' | 'committee-manage';
};
