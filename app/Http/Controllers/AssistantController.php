<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Assistant;
use App\Ai\PendingActionService;
use App\Http\Requests\AssistantChatRequest;
use App\Models\User;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssistantController extends Controller
{
    /**
     * Stream the assistant's reply as Server-Sent Events.
     *
     * Wire format — `data: {…}` events terminated by `[DONE]`:
     *  - `{"type":"reasoning","text":"…"}` — chain-of-thought deltas
     *  - `{"type":"delta","text":"…"}`     — answer text deltas
     *  - `{"type":"tool_call","id":"…","label":"…"}`   — a tool started (live activity)
     *  - `{"type":"tool_result","id":"…","ok":true}`   — that tool finished
     *  - `{"type":"tool_confirm","id":"…","summary":"…","changes":[…]}` — a write awaiting confirmation
     *  - `{"type":"conversation","id":"…"}` — the conversation id to continue
     *  - `{"type":"error","message":"…"}`   — a failure (also logged server-side)
     *
     * The `X-Accel-Buffering: no` header tells nginx not to buffer the response
     * so chunks reach the browser as they are produced.
     */
    public function __invoke(AssistantChatRequest $request): StreamedResponse
    {
        $user = $request->user();
        $message = (string) $request->validated('message');
        $conversationId = $request->validated('conversation_id');

        $assistant = new Assistant($user);

        // Conversation memory is only persisted for authenticated users; guests
        // chat statelessly so there is no cross-guest history to leak.
        if ($user !== null) {
            if ($conversationId !== null && $this->ownsConversation($user, $conversationId)) {
                $assistant->continue($conversationId, as: $user);
            } else {
                $assistant->forUser($user);
            }
        }

        $stream = $assistant->stream($message);

        return response()->stream(function () use ($stream) {
            try {
                foreach ($stream as $event) {
                    if ($event instanceof ReasoningDelta && $event->delta !== '') {
                        yield $this->sse(['type' => 'reasoning', 'text' => $event->delta]);
                    } elseif ($event instanceof TextDelta && $event->delta !== '') {
                        yield $this->sse(['type' => 'delta', 'text' => $event->delta]);
                    } elseif ($event instanceof ToolCall) {
                        // Surface tool usage live so multi-step turns show progress
                        // instead of an idle spinner while the model works.
                        yield $this->sse([
                            'type' => 'tool_call',
                            'id' => $event->toolCall->id,
                            'label' => $this->toolLabel($event->toolCall->name),
                        ]);
                    } elseif ($event instanceof ToolResult) {
                        yield $this->sse([
                            'type' => 'tool_result',
                            'id' => $event->toolResult->id,
                            'ok' => $event->successful,
                        ]);
                    }
                }

                // Emit any write-tool confirm cards produced during this turn.
                foreach (app(PendingActionService::class)->all() as $action) {
                    yield $this->sse(['type' => 'tool_confirm', ...$action]);
                }

                // The conversation id is only resolved once the stream has been
                // fully consumed (and the conversation persisted) above.
                yield $this->sse(['type' => 'conversation', 'id' => $stream->conversationId]);
            } catch (\Throwable $e) {
                // The exception happens inside the stream generator (after the
                // 200 is sent), so it would otherwise vanish. Log it and forward
                // a message to the client — verbatim in debug, generic in prod.
                report($e);

                yield $this->sse([
                    'type' => 'error',
                    'message' => config('app.debug')
                        ? class_basename($e).': '.$e->getMessage()
                        : 'حدث خطأ أثناء معالجة طلبك. حاول مرة أخرى.',
                ]);
            }

            yield "data: [DONE]\n\n";
        }, headers: [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Human-readable, localized label for a tool the model just invoked. Tool
     * names are class basenames (e.g. "RegisterForEvent"); each maps to an
     * `assistant.activity.*` line, falling back to a generic "working" label.
     */
    private function toolLabel(string $toolName): string
    {
        $key = "assistant.activity.{$toolName}";
        $label = __($key);

        return $label === $key ? __('assistant.activity.default') : $label;
    }

    /**
     * Encode a single Server-Sent Event line.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sse(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
    }

    /**
     * Whether the conversation belongs to the given user. Prevents a user from
     * continuing — and thereby reading — another user's conversation history.
     */
    private function ownsConversation(User $user, string $conversationId): bool
    {
        return Conversation::query()
            ->whereKey($conversationId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
