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
            ['label' => 'مساحات العمل', 'url' => route('workspaces')],
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

            foreach ($this->user->managedWorkspaces()->take(3) as $workspace) {
                $pages[] = [
                    'label' => "إدارة مساحة العمل: {$workspace->name}",
                    'url' => route('workspaces.manage', [$workspace], absolute: false),
                ];
            }

            foreach ($this->user->managedProjects()->take(5) as $project) {
                $pages[] = [
                    'label' => "إدارة المشروع: {$project->name}",
                    'url' => route('projects.manage', [$project->workspace_id, $project->id], absolute: false),
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
