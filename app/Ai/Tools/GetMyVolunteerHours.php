<?php

namespace App\Ai\Tools;

use App\Models\VolunteerHour;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The current user's volunteer hours, per event plus an approved total.
 */
class GetMyVolunteerHours extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the current user\'s volunteer hours per event and their total approved hours. '
            .'Use for "how many volunteer hours do I have?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $records = $this->user->volunteerHours()
            ->with('event:id,title,starts_at')
            ->orderByDesc('approved_at')
            ->get();

        $rows = $records->map(fn (VolunteerHour $record): array => [
            'event' => $record->event?->title,
            'hours' => (float) $record->hours,
            'approved' => $record->approved_at !== null,
            'approvedAt' => $record->approved_at?->toIso8601String(),
        ])->all();

        return $this->json([
            'volunteerHours' => $rows,
            'totalApprovedHours' => (float) $records->whereNotNull('approved_at')->sum('hours'),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
