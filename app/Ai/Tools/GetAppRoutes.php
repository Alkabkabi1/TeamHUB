<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Returns navigable page URLs so the assistant can share direct links with the
 * user. Data-bearing URLs (clubs, events, committees, news articles) are
 * returned by their respective data tools and are not duplicated here.
 */
class GetAppRoutes extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get a list of navigable app page links to share with the user. Use when the user asks '
            .'where to find something or how to navigate to a specific section. Note: individual club, '
            .'event, committee, and news article URLs are returned by the data tools (FindClubs, '
            .'FindEvents, etc.) — use those instead when you already have the data.';
    }

    public function handle(Request $request): Stringable|string
    {
        $pages = [
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'جميع الأندية', 'url' => route('clubs')],
            ['label' => 'جميع الفعاليات', 'url' => route('events')],
            ['label' => 'الأخبار', 'url' => route('news.index')],
            ['label' => 'الموارد والملفات', 'url' => route('resources')],
            ['label' => 'الدعم والتواصل', 'url' => route('support')],
        ];

        if ($this->user === null) {
            $pages[] = ['label' => 'تسجيل الدخول', 'url' => route('login')];
            $pages[] = ['label' => 'إنشاء حساب جديد', 'url' => route('register')];
            $pages[] = ['label' => 'استعادة كلمة المرور', 'url' => route('password.request')];
        } else {
            $pages[] = ['label' => 'لوحة الطالب (فعالياتي، أنديتي، شهاداتي، ساعاتي التطوعية)', 'url' => route('student-dashboard')];
        }

        return $this->json(['pages' => $pages]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
