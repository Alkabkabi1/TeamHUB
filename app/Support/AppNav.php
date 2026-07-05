<?php

namespace App\Support;

use App\Models\User;

class AppNav
{
    /**
     * @return list<array{href: string, label: string, icon: string, badge?: int}>
     */
    public static function items(User $user): array
    {
        $items = [
            [
                'href' => route('dashboard', absolute: false),
                'label' => __('dashboard.nav.home'),
                'icon' => 'home',
            ],
            [
                'href' => route('projects', absolute: false),
                'label' => __('dashboard.nav.projects'),
                'icon' => 'projects',
            ],
            [
                'href' => route('tasks', absolute: false),
                'label' => __('dashboard.nav.tasks'),
                'icon' => 'tasks',
            ],
        ];

        if ($user->isMember()) {
            $items[] = [
                'href' => route('my-tasks', absolute: false),
                'label' => __('dashboard.nav.my_tasks'),
                'icon' => 'deliverable',
            ];
        }

        $managedWorkspace = $user->managedWorkspace();
        if ($managedWorkspace !== null) {
            $items[] = [
                'href' => route('workspaces.manage.members', $managedWorkspace, absolute: false),
                'label' => __('dashboard.nav.team'),
                'icon' => 'team',
            ];
        }

        $managedProject = $user->managedProjects()->first();
        if ($managedProject !== null) {
            $items[] = [
                'href' => route('projects.files.index', [$managedProject->workspace_id, $managedProject], absolute: false),
                'label' => __('dashboard.nav.files'),
                'icon' => 'files',
            ];
            $items[] = [
                'href' => route('projects.reports.members', [$managedProject->workspace_id, $managedProject], absolute: false),
                'label' => __('dashboard.nav.reports'),
                'icon' => 'reports',
            ];
        } elseif ($managedWorkspace !== null) {
            $items[] = [
                'href' => route('workspaces.reports.members', $managedWorkspace, absolute: false),
                'label' => __('dashboard.nav.reports'),
                'icon' => 'reports',
            ];
        }

        $unread = $user->unreadNotifications()->count();
        $items[] = [
            'href' => route('notifications.index', absolute: false),
            'label' => __('dashboard.nav.notifications'),
            'icon' => 'notifications',
            'badge' => $unread > 0 ? $unread : null,
        ];

        if ($user->isAdmin()) {
            $items[] = [
                'href' => route('filament.admin.pages.dashboard', absolute: false),
                'label' => __('dashboard.nav.admin'),
                'icon' => 'reports',
            ];
        }

        return $items;
    }
}
