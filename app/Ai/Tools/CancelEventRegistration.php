<?php

namespace App\Ai\Tools;

use App\Models\Event;
use App\Models\EventAttendance;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Cancel the current user's event registration (RSVP) using the two-phase
 * confirm flow. Guards against cancelling past events.
 */
class CancelEventRegistration extends WriteTool
{
    public function description(): Stringable|string
    {
        return "Cancel the current user's event registration (RSVP).";
    }

    protected function preview(Request $request): array
    {
        $event = $this->resolveEvent($request['event'] ?? null);

        if ($event === null) {
            return ['error' => 'لم يتم العثور على الفعالية. حاول استخدام اسمها الكامل أو جزء منه.'];
        }

        if (! $this->user->isStudent()) {
            return ['error' => 'فقط الطلاب يمكنهم إلغاء تسجيلهم في الفعاليات.'];
        }

        if ($event->starts_at?->isPast()) {
            return ['error' => 'لا يمكن إلغاء التسجيل في فعالية انتهت.'];
        }

        $existing = EventAttendance::where('user_id', $this->user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing === null) {
            return ['error' => 'لستَ مسجّلًا في هذه الفعالية.'];
        }

        return [
            'summary' => "إلغاء التسجيل في فعالية \"{$event->title}\"",
            'changes' => ["حذف التسجيل من فعالية \"{$event->title}\""],
            'params' => ['event_id' => $event->id],
        ];
    }

    public function execute(array $params): array
    {
        $event = Event::findOrFail($params['event_id']);

        if ($event->starts_at?->isPast()) {
            return ['success' => false, 'message' => 'لا يمكن إلغاء التسجيل في فعالية انتهت.'];
        }

        EventAttendance::where('user_id', $this->user->id)
            ->where('event_id', $event->id)
            ->delete();

        return ['success' => true, 'message' => "تم إلغاء تسجيلك في فعالية \"{$event->title}\"."];
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
