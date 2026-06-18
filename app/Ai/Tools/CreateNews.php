<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Create and publish a news post for a club or committee using the two-phase confirm flow.
 */
class CreateNews extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Create and publish a news post for a club or committee. Images are not supported via chat.';
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        $committee = null;

        if (! empty($request['committee'])) {
            $committee = $this->resolveCommittee($request['committee'], $club);
        }

        if ($committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageNews->value, $committee)) {
                return ['error' => 'ليس لديك صلاحية لنشر الأخبار في هذا النادي.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $club)) {
                return ['error' => 'ليس لديك صلاحية لنشر الأخبار في هذا النادي.'];
            }
        }

        $title = trim((string) ($request['title'] ?? ''));

        if ($title === '') {
            return ['error' => 'العنوان مطلوب.'];
        }

        $body = trim((string) ($request['body'] ?? ''));

        if ($body === '') {
            return ['error' => 'محتوى الخبر مطلوب.'];
        }

        return [
            'summary' => "نشر خبر جديد: \"{$title}\" في نادي {$club->name}",
            'changes' => ["إنشاء منشور بعنوان \"{$title}\" ونشره فورًا"],
            'params' => [
                'club_id' => $club->id,
                'committee_id' => $committee?->id,
                'title' => $title,
                'body' => $body,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $club = Club::findOrFail($params['club_id']);

        $committee = isset($params['committee_id']) && $params['committee_id'] !== null
            ? Committee::findOrFail($params['committee_id'])
            : null;

        if ($committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageNews->value, $committee)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لنشر الأخبار في هذا النادي.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $club)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لنشر الأخبار في هذا النادي.'];
            }
        }

        $post = Post::create([
            'title' => $params['title'],
            'body' => $params['body'],
            'club_id' => $params['club_id'],
            'committee_id' => $params['committee_id'],
            'user_id' => $this->user->id,
            'published_at' => now(),
        ]);

        $recipientIds = ClubMembership::where('club_id', $params['club_id'])
            ->where('status', 'approved')
            ->where('user_id', '!=', $this->user->id)
            ->pluck('user_id')
            ->unique();

        $recipients = User::whereIn('id', $recipientIds)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewPostNotification($post));
        }

        return [
            'success' => true,
            'message' => "تم نشر الخبر \"{$post->title}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('Club name or ID.')
                ->required(),
            'committee' => $schema->string()
                ->description('Committee name or ID.'),
            'title' => $schema->string()
                ->description('Post title.')
                ->required(),
            'body' => $schema->string()
                ->description('Post body content.')
                ->required(),
        ];
    }
}
