<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Delete a news post using the two-phase confirm flow.
 */
class DeleteNews extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Delete a news post. Requires ManageNews capability.';
    }

    protected function preview(Request $request): array
    {
        $post = Post::with('club', 'committee')->find($request['post_id']);

        if ($post === null) {
            return ['error' => 'لم يتم العثور على الخبر.'];
        }

        $authTarget = $post->committee ?? $post->club;

        if ($post->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageNews->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لحذف هذا الخبر.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لحذف هذا الخبر.'];
            }
        }

        return [
            'summary' => "حذف خبر \"{$post->title}\"",
            'changes' => ["حذف المنشور \"{$post->title}\" نهائيًا"],
            'params' => ['post_id' => $post->id],
        ];
    }

    public function execute(array $params): array
    {
        $post = Post::with('club', 'committee')->findOrFail($params['post_id']);
        $authTarget = $post->committee ?? $post->club;

        if ($post->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageNews->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لحذف هذا الخبر.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لحذف هذا الخبر.'];
            }
        }

        $title = $post->title;
        $post->delete();

        return [
            'success' => true,
            'message' => "تم حذف الخبر \"{$title}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('The numeric ID of the post to delete.')
                ->required(),
        ];
    }
}
