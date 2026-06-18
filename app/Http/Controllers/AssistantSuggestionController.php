<?php

namespace App\Http\Controllers;

use App\Ai\AssistantSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantSuggestionController extends Controller
{
    /**
     * Return a handful of starter prompts for the assistant's empty state,
     * tailored to the current user (or a guest) and the available data.
     */
    public function __invoke(Request $request, AssistantSuggestionService $suggestions): JsonResponse
    {
        return response()->json([
            'suggestions' => $suggestions->for($request->user()),
        ]);
    }
}
