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
                'href' => route('home', absolute: false),
                'label' => __('dashboard.nav.workspaces'),
                'icon' => 'workspaces',
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
