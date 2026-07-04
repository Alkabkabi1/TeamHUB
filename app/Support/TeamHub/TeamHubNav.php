<?php

namespace App\Support\TeamHub;

use App\Models\User;

class TeamHubNav
{
    /**
     * @return list<array{href: string, label: string, icon: string, badge?: int}>
     */
    public static function items(User $user): array
    {
        $items = [
            [
                'href' => route('hub.dashboard', absolute: false),
                'label' => __('hub.nav.home'),
                'icon' => 'home',
            ],
            [
                'href' => route('hub.projects', absolute: false),
                'label' => __('hub.nav.projects'),
                'icon' => 'projects',
            ],
            [
                'href' => route('hub.tasks', absolute: false),
                'label' => __('hub.nav.tasks'),
                'icon' => 'tasks',
            ],
        ];

        if ($user->isStudent()) {
            $items[] = [
                'href' => route('my-tasks', absolute: false),
                'label' => __('hub.nav.my_tasks'),
                'icon' => 'deliverable',
            ];
        }

        $managedClub = $user->managedClub();
        if ($managedClub !== null) {
            $items[] = [
                'href' => route('clubs.manage.members', $managedClub, absolute: false),
                'label' => __('hub.nav.team'),
                'icon' => 'team',
            ];
        }

        $items[] = [
            'href' => route('events', absolute: false),
            'label' => __('hub.nav.calendar'),
            'icon' => 'calendar',
        ];

        $managedCommittee = $user->managedCommittees()->first();
        if ($managedCommittee !== null) {
            $items[] = [
                'href' => route('committees.files.index', [$managedCommittee->club_id, $managedCommittee], absolute: false),
                'label' => __('hub.nav.files'),
                'icon' => 'files',
            ];
            $items[] = [
                'href' => route('committees.reports.members', [$managedCommittee->club_id, $managedCommittee], absolute: false),
                'label' => __('hub.nav.reports'),
                'icon' => 'reports',
            ];
        } elseif ($managedClub !== null) {
            $items[] = [
                'href' => route('clubs.reports.members', $managedClub, absolute: false),
                'label' => __('hub.nav.reports'),
                'icon' => 'reports',
            ];
        }

        $unread = $user->unreadNotifications()->count();
        $items[] = [
            'href' => route('notifications.index', absolute: false),
            'label' => __('hub.nav.notifications'),
            'icon' => 'notifications',
            'badge' => $unread > 0 ? $unread : null,
        ];

        if ($user->isUniversityStaff()) {
            $items[] = [
                'href' => route('filament.admin.pages.dashboard', absolute: false),
                'label' => __('hub.nav.admin'),
                'icon' => 'reports',
            ];
        }

        return $items;
    }
}
