<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Log or update volunteer hours for a club member. The event is optional: when
 * omitted, the hours are recorded for the member as general (activity-less)
 * hours. Requires ManageVolunteerHours capability.
 */
class LogVolunteerHours extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Log or update volunteer hours for a club member. The event is optional — if the user does not name an activity, '
            .'record the hours without one (general hours). Requires ManageVolunteerHours capability.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('Club name or numeric ID.')
                ->required(),
            'user' => $schema->string()
                ->description('Club member name or numeric ID.')
                ->required(),
            'event' => $schema->string()
                ->description('Optional event name or numeric ID. Omit when the hours are not tied to a specific activity.'),
            'hours' => $schema->number()
                ->description('Number of volunteer hours (positive decimal).')
                ->required(),
        ];
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club']);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! Gate::allows(ClubCapability::ManageVolunteerHours->value, $club)) {
            return ['error' => 'ليس لديك صلاحية لتسجيل ساعات التطوع في هذا النادي.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null, $club);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على عضو بهذا الاسم في النادي. حاول كتابة الاسم كاملاً أو جزءاً منه.'];
        }

        $eventInput = $request['event'] ?? null;
        $event = null;

        if (filled($eventInput)) {
            $event = $this->resolveEvent($eventInput, $club);

            if ($event === null) {
                return ['error' => 'لم يتم العثور على الفعالية. حاول استخدام اسمها أو جزء منه، أو سجّل الساعات دون فعالية.'];
            }
        }

        $hours = (float) ($request['hours'] ?? 0);

        if ($hours <= 0) {
            return ['error' => 'يجب أن تكون الساعات قيمة موجبة.'];
        }

        $existing = $event !== null
            ? VolunteerHour::where('user_id', $targetUser->id)->where('event_id', $event->id)->first()
            : null;

        $action = $existing ? 'تحديث' : 'تسجيل';
        $context = $event !== null ? "في فعالية \"{$event->title}\"" : 'كساعات تطوع عامة (دون فعالية)';

        return [
            'summary' => "{$action} ساعات تطوع لـ {$targetUser->name} {$context}",
            'changes' => [
                $existing
                    ? "تغيير ساعات التطوع من {$existing->hours} إلى {$hours} ساعة لـ {$targetUser->name}"
                    : "تسجيل {$hours} ساعة تطوع لـ {$targetUser->name} {$context}",
            ],
            'params' => [
                'user_id' => $targetUser->id,
                'event_id' => $event?->id,
                'hours' => $hours,
                'club_id' => $club->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $club = $this->resolveClub($params['club_id']);

        abort_unless(Gate::allows(ClubCapability::ManageVolunteerHours->value, $club), 403);

        $eventId = $params['event_id'] ?? null;

        $attributes = [
            'club_id' => $club->id,
            'hours' => $params['hours'],
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ];

        if ($eventId !== null) {
            VolunteerHour::updateOrCreate(
                ['user_id' => $params['user_id'], 'event_id' => $eventId],
                $attributes,
            );
        } else {
            VolunteerHour::create(array_merge($attributes, [
                'user_id' => $params['user_id'],
                'event_id' => null,
            ]));
        }

        $user = User::find($params['user_id']);
        $event = $eventId !== null ? Event::find($eventId) : null;
        $context = $event !== null ? "في فعالية \"{$event->title}\"" : 'كساعات تطوع عامة';

        return [
            'success' => true,
            'message' => "تم تسجيل {$params['hours']} ساعة تطوع لـ {$user->name} {$context}.",
        ];
    }
}
