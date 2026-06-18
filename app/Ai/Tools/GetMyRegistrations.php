<?php

namespace App\Ai\Tools;

use App\Models\EventAttendance;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The current user's event registrations/attendances, optionally limited to
 * upcoming or past events.
 */
class GetMyRegistrations extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the events the current user has registered for, with their registration status. '
            .'Optionally limit to upcoming or past events. Use for "what am I signed up for?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $scope = (string) ($request['scope'] ?? 'all');

        $registrations = $this->user->eventAttendances()
            ->with('event:id,title,starts_at,location,club_id', 'event.club:id,name')
            ->whereHas('event', function ($query) use ($scope) {
                if ($scope === 'upcoming') {
                    $query->where('starts_at', '>=', now());
                } elseif ($scope === 'past') {
                    $query->where('starts_at', '<', now());
                }
            })
            ->get()
            ->sortByDesc(fn (EventAttendance $attendance) => $attendance->event?->starts_at)
            ->map(fn (EventAttendance $attendance): array => [
                'event' => $attendance->event?->title,
                'club' => $attendance->event?->club?->name,
                'status' => $attendance->status,
                'startsAt' => $attendance->event?->starts_at?->toIso8601String(),
                'location' => $attendance->event?->location,
                'checkedInAt' => $attendance->checked_in_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->json(['registrations' => $registrations]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum(['all', 'upcoming', 'past'])
                ->description('Limit to "upcoming" or "past" events, or "all" (default).'),
        ];
    }
}
