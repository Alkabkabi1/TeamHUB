<?php

namespace App\Ai\Tools;

use App\Models\AttendanceCheckin;
use App\Models\EventAttendance;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The current user's recorded attendance (per-day QR check-ins) across events.
 */
class GetMyAttendance extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the events the current user actually attended, with the dates their attendance was '
            .'recorded (QR check-ins). Use for "which events did I attend?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $attendance = $this->user->eventAttendances()
            ->whereHas('checkins')
            ->with('event:id,title,starts_at', 'checkins')
            ->get()
            ->sortByDesc(fn (EventAttendance $a) => $a->event?->starts_at)
            ->map(fn (EventAttendance $a): array => [
                'event' => $a->event?->title,
                'startsAt' => $a->event?->starts_at?->toIso8601String(),
                'daysAttended' => $a->checkins
                    ->sortBy('attended_on')
                    ->map(fn (AttendanceCheckin $c): ?string => $c->attended_on?->toDateString())
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return $this->json(['attendance' => $attendance]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
