<?php

namespace App\Ai\Tools;

use App\Enums\EventAttendanceStatus;
use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Full details for a single active event, including capacity/registration
 * status and — for an authenticated user — whether they are registered.
 */
class GetEventDetails extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get full details for a specific event/activity by name or id: description, schedule, location, '
            .'capacity, whether it is open or full, and (if signed in) whether the user is registered.';
    }

    public function handle(Request $request): Stringable|string
    {
        $identifier = trim((string) ($request['event'] ?? ''));

        if ($identifier === '') {
            return $this->json(['error' => 'Please provide an event name or id.']);
        }

        $event = Event::query()
            ->active()
            ->with('club:id,name', 'committee:id,name')
            ->when(
                ctype_digit($identifier),
                fn ($q) => $q->whereKey((int) $identifier),
                fn ($q) => $q->where('title', 'like', "%{$identifier}%"),
            )
            ->orderBy('starts_at')
            ->first();

        if ($event === null) {
            return $this->json(['error' => 'No active event matched that name.']);
        }

        $myStatus = null;

        if ($this->user !== null) {
            $myStatus = $event->attendances()
                ->where('user_id', $this->user->id)
                ->value('status');
        }

        return $this->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'club' => $event->club?->name,
                'committee' => $event->committee?->name,
                'startsAt' => $event->starts_at?->toIso8601String(),
                'endsAt' => $event->ends_at?->toIso8601String(),
                'location' => $event->location,
                'capacity' => $event->capacity,
                'seatsTaken' => $event->registeredCount(),
                'isFull' => $event->isFull(),
                'openForRegistration' => $event->isOpenForRegistration(),
                'myRegistrationStatus' => $myStatus,
                'amRegistered' => $myStatus !== null
                    && in_array($myStatus, EventAttendanceStatus::registeredValues(), true),
                'url' => route('events.show', $event),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()
                ->description('The event title (or numeric id) to look up.')
                ->required(),
        ];
    }
}
