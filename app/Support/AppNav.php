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
        $items = [];

        if ($user->usesMyTasksHome()) {
            $items[] = [
                'href' => route('my-tasks', absolute: false),
                'label' => __('dashboard.nav.my_tasks'),
                'icon' => 'tasks',
            ];
        } else {
            $items[] = [
                'href' => route('dashboard', absolute: false),
                'label' => __('dashboard.nav.home'),
                'icon' => 'home',
            ];

            $managedProject = $user->managedProjects()->first();
            if ($managedProject !== null) {
                $items[] = [
                    'href' => route('projects.tasks.index', [$managedProject->workspace_id, $managedProject], absolute: false),
                    'label' => __('dashboard.nav.tasks'),
                    'icon' => 'tasks',
                ];
            }

            $managedWorkspace = $user->managedWorkspace();
            if ($managedWorkspace !== null) {
                $items[] = [
                    'href' => route('workspaces.manage', $managedWorkspace, absolute: false),
                    'label' => __('dashboard.nav.workspaces'),
                    'icon' => 'workspaces',
                ];
            }
        }

        $unread = $user->unreadNotifications()->count();
        $items[] = [
            'href' => route('notifications.index', absolute: false),
            'label' => __('dashboard.nav.notifications'),
            'icon' => 'notifications',
            'badge' => $unread > 0 ? $unread : null,
        ];

        $items[] = [
            'href' => route('profile.edit', absolute: false),
            'label' => __('dashboard.nav.settings'),
            'icon' => 'settings',
        ];

        return $items;
    }
}
