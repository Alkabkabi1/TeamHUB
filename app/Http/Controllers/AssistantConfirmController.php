<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AssistantConfirmController extends Controller
{
    /**
     * Execute a pending AI write action that the user has explicitly confirmed.
     *
     * The cache entry is written by WriteTool::handle() and expires after
     * 10 minutes. Ownership is validated against the authenticated user so a
     * user cannot execute another user's pending action.
     */
    public function __invoke(Request $request, string $actionId): JsonResponse
    {
        $user = $request->user();

        /** @var array{tool: class-string, params: array<string, mixed>, user_id: int}|null $pending */
        $pending = Cache::get("ai_pending_action:{$actionId}");

        if ($pending === null || (int) $pending['user_id'] !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية هذا الإجراء أو لم يعد متاحًا.',
            ], 404);
        }

        Cache::forget("ai_pending_action:{$actionId}");

        $tool = app($pending['tool'], ['user' => $user]);

        return response()->json($tool->execute($pending['params']));
    }
}
