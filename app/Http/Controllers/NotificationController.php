<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'action_label' => $notification->data['action_label'] ?? __('notifications.open'),
                'action_url' => $notification->data['action_url'] ?? null,
                'kind' => $notification->data['kind'] ?? 'generic',
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Notifications', [
            'unreadNotifications' => $notifications->where('read_at', null)->values(),
            'readNotifications' => $notifications->where('read_at', '!=', null)->values(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
