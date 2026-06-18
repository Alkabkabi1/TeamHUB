<?php

namespace App\Ai\Tools;

use App\Ai\PendingActionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Base class for write tools. Subclasses implement preview() and execute()
 * instead of handle(); the base wires them into the two-phase confirm flow:
 *
 *  1. handle() calls preview() to dry-run the action and caches a PendingAction.
 *  2. The SSE controller emits a "tool_confirm" event with the action id.
 *  3. The user clicks Confirm in the UI → POST /assistant/confirm/{id}.
 *  4. AssistantConfirmController resolves the tool and calls execute($params).
 */
abstract class WriteTool extends AssistantTool
{
    /**
     * Compute a dry-run preview of the action. Return a summary, a human-
     * readable list of what will change, and the params to pass to execute().
     * Return ['error' => '...'] to fail immediately without a confirm card.
     *
     * @return array{summary: string, changes: list<string>, params: array<string, mixed>}|array{error: string}
     */
    abstract protected function preview(Request $request): array;

    /**
     * Execute the action with the params returned by preview(). Called by
     * AssistantConfirmController after the user confirms in the UI.
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    abstract public function execute(array $params): array;

    /**
     * Produce a pending-confirmation response: cache the action and register
     * it with PendingActionService so the SSE stream can surface the confirm
     * card. Returning an error from preview() short-circuits this entirely.
     */
    final public function handle(Request $request): Stringable|string
    {
        $preview = $this->preview($request);

        if (isset($preview['error'])) {
            return $this->json(['error' => $preview['error']]);
        }

        $actionId = Str::uuid()->toString();

        Cache::put(
            "ai_pending_action:{$actionId}",
            [
                'tool' => static::class,
                'params' => $preview['params'],
                'user_id' => $this->user->id,
            ],
            now()->addMinutes(10),
        );

        app(PendingActionService::class)->add(
            id: $actionId,
            summary: $preview['summary'],
            changes: $preview['changes'],
        );

        return $this->json([
            'status' => 'pending_confirmation',
            'action_id' => $actionId,
            'summary' => $preview['summary'],
        ]);
    }
}
