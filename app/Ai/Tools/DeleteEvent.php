<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Soft-delete an event using the two-phase confirm flow.
 */
class DeleteEvent extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Soft-delete an event. Requires ManageEvents capability.';
    }

    protected function preview(Request $request): array
    {
        $event = $this->resolveEvent($request['event'] ?? null);

        if ($event === null) {
            return ['error' => 'لم يتم العثور على الفعالية. حاول استخدام اسمها الكامل أو جزء منه.'];
        }

        $event->loadMissing('club', 'committee');

        $authTarget = $event->committee ?? $event->club;

        if ($event->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageEvents->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لحذف هذه الفعالية.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لحذف هذه الفعالية.'];
            }
        }

        return [
            'summary' => "حذف فعالية \"{$event->title}\"",
            'changes' => ["حذف فعالية \"{$event->title}\" (لن يتمكن المتقدمون من رؤيتها بعد الآن)"],
            'params' => ['event_id' => $event->id],
        ];
    }

    public function execute(array $params): array
    {
        $event = Event::with('club', 'committee')->findOrFail($params['event_id']);
        $authTarget = $event->committee ?? $event->club;

        if ($event->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageEvents->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لحذف هذه الفعالية.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لحذف هذه الفعالية.'];
            }
        }

        $title = $event->title;
        $event->delete();

        return [
            'success' => true,
            'message' => "تم حذف فعالية \"{$title}\".",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()
                ->description('Event name or numeric ID.')
                ->required(),
        ];
    }
}
