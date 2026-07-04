<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Returns navigable TeamHUB page URLs so the assistant can share direct links
 * with the user. Task and project deep links are returned by the task tools.
 */
class GetAppRoutes extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a list of TeamHUB pages to help the user navigate. Use when the user asks where to '
            .'find their tasks, dashboard, notifications, project management pages, or sign-in screens.';
    }

    public function handle(Request $request): Stringable|string
    {
        $pages = [
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'مساحات العمل', 'url' => route('clubs')],
            ['label' => 'الدعم والتواصل', 'url' => route('support')],
        ];

        if ($this->user === null) {
            $pages[] = ['label' => 'تسجيل الدخول', 'url' => route('login')];
            $pages[] = ['label' => 'إنشاء حساب جديد', 'url' => route('register')];
            $pages[] = ['label' => 'استعادة كلمة المرور', 'url' => route('password.request')];
        } else {
            $pages[] = ['label' => 'لوحة TeamHUB', 'url' => route('student-dashboard')];
            $pages[] = ['label' => 'مهامي', 'url' => route('my-tasks')];
            $pages[] = ['label' => 'الإشعارات', 'url' => route('notifications.index')];

            foreach ($this->user->managedClubs()->take(3) as $club) {
                $pages[] = [
                    'label' => "إدارة مساحة العمل: {$club->name}",
                    'url' => route('clubs.manage', [$club], absolute: false),
                ];
            }

            foreach ($this->user->managedCommittees()->take(5) as $committee) {
                $pages[] = [
                    'label' => "إدارة المشروع: {$committee->name}",
                    'url' => route('committees.manage', [$committee->club_id, $committee->id], absolute: false),
                ];
            }
        }

        return $this->json(['pages' => $pages]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
