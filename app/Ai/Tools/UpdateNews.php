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
 * Update an existing news post's title and/or body using the two-phase confirm flow.
 */
class UpdateNews extends WriteTool
{
    public function description(): Stringable|string
    {
        return "Update an existing news post's title and/or body. Images cannot be changed via chat.";
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
                return ['error' => 'ليس لديك صلاحية لتعديل هذا الخبر.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لتعديل هذا الخبر.'];
            }
        }

        $changes = [];
        $params = ['post_id' => $post->id];

        if (array_key_exists('title', $request->all()) && $request['title'] !== $post->title) {
            $changes[] = "العنوان: \"{$post->title}\" → \"{$request['title']}\"";
            $params['title'] = $request['title'];
        }

        if (array_key_exists('body', $request->all()) && $request['body'] !== $post->body) {
            $changes[] = 'المحتوى: تم تعديله';
            $params['body'] = $request['body'];
        }

        if (empty($changes)) {
            return ['error' => 'لم يتم تحديد أي تعديلات.'];
        }

        return [
            'summary' => "تعديل خبر \"{$post->title}\"",
            'changes' => $changes,
            'params' => $params,
        ];
    }

    public function execute(array $params): array
    {
        $post = Post::with('club', 'committee')->findOrFail($params['post_id']);
        $authTarget = $post->committee ?? $post->club;

        if ($post->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageNews->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لتعديل هذا الخبر.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageNews->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لتعديل هذا الخبر.'];
            }
        }

        $data = [];

        foreach (['title', 'body'] as $field) {
            if (array_key_exists($field, $params)) {
                $data[$field] = $params[$field];
            }
        }

        $post->update($data);

        return [
            'success' => true,
            'message' => "تم تعديل الخبر \"{$post->title}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('The numeric ID of the post to update.')
                ->required(),
            'title' => $schema->string()
                ->description('New title.'),
            'body' => $schema->string()
                ->description('New body content.'),
        ];
    }
}
