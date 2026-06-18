<?php

namespace App\Ai\Tools;

use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List active, publicly-listed events — by default upcoming ones, optionally
 * only those still open for registration, optionally filtered to a club.
 */
class FindEvents extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List upcoming public events/activities. Optionally filter to events still open for '
            .'registration or to a specific club. Use this for questions like "what activities are coming up?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $scope = (string) ($request['scope'] ?? 'upcoming');
        $clubName = trim((string) ($request['club'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 10), 1), 25);

        $events = Event::query()
            ->active()
            ->upcoming()
            ->when($clubName !== '', fn ($q) => $q->whereHas('club', fn ($c) => $c->where('name', 'like', "%{$clubName}%")))
            ->with('club:id,name')
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->when($scope === 'open', fn ($events) => $events->filter->isOpenForRegistration())
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'club' => $event->club?->name,
                'startsAt' => $event->starts_at?->toIso8601String(),
                'endsAt' => $event->ends_at?->toIso8601String(),
                'location' => $event->location,
                'capacity' => $event->capacity,
                'seatsTaken' => $event->registeredCount(),
                'openForRegistration' => $event->isOpenForRegistration(),
                'url' => route('events.show', $event),
            ])
            ->values()
            ->all();

        return $this->json(['events' => $events]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum(['upcoming', 'open'])
                ->description('"upcoming" for all future events (default), "open" for only those still open for registration.'),
            'club' => $schema->string()
                ->description('Optional club name to filter events by.'),
            'limit' => $schema->integer()->min(1)->max(25)
                ->description('Maximum number of events to return (default 10).'),
        ];
    }
}
