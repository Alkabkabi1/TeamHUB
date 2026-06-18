<?php

namespace App\Ai\Tools;

use App\Enums\EventAttendanceStatus;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Notifications\RsvpConfirmationNotification;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Register the current user (student) for an event using the two-phase
 * confirm flow. Validates eligibility in preview() before committing.
 */
class RegisterForEvent extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Register the current user (student) for an event.';
    }

    protected function preview(Request $request): array
    {
        $event = $this->resolveEvent($request['event'] ?? null);

        if ($event === null) {
            return ['error' => 'لم يتم العثور على الفعالية. حاول استخدام اسمها الكامل أو جزء منه.'];
        }

        if (! $this->user->isStudent()) {
            return ['error' => 'فقط الطلاب يمكنهم التسجيل في الفعاليات.'];
        }

        if (! $event->isOpenForRegistration()) {
            return ['error' => 'هذه الفعالية غير متاحة للتسجيل حاليًا.'];
        }

        $existing = EventAttendance::where('user_id', $this->user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing !== null) {
            return ['error' => 'أنت مسجّل بالفعل في هذه الفعالية.'];
        }

        if ($event->isFull()) {
            return ['error' => 'اكتملت سعة هذه الفعالية.'];
        }

        return [
            'summary' => "التسجيل في فعالية \"{$event->title}\"",
            'changes' => [
                "إضافة تسجيل في فعالية \"{$event->title}\" بتاريخ ".$event->starts_at?->translatedFormat('d F Y'),
            ],
            'params' => ['event_id' => $event->id],
        ];
    }

    public function execute(array $params): array
    {
        $event = Event::findOrFail($params['event_id']);

        if (! $event->isOpenForRegistration()) {
            return ['success' => false, 'message' => 'هذه الفعالية لم تعد متاحة للتسجيل.'];
        }

        $attendance = EventAttendance::updateOrCreate(
            ['user_id' => $this->user->id, 'event_id' => $event->id],
            ['status' => EventAttendanceStatus::Registered->value],
        );

        if ($attendance->wasRecentlyCreated) {
            $this->user->notify(new RsvpConfirmationNotification($event));
        }

        return ['success' => true, 'message' => "تم التسجيل في فعالية \"{$event->title}\" بنجاح."];
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
