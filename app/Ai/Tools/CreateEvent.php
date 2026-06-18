<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Enums\EventStatus;
use App\Models\Club;
use App\Models\Committee;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Create a new event for a club or committee using the two-phase confirm flow.
 */
class CreateEvent extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Create a new event for a club or committee. Requires ManageEvents capability. Images are not supported via chat.';
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club'] ?? null);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        $committee = null;

        if (! empty($request['committee'])) {
            $committee = $this->resolveCommittee($request['committee'], $club);
        }

        if ($committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageEvents->value, $committee)) {
                return ['error' => 'ليس لديك صلاحية لإنشاء فعاليات في هذا النادي.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $club)) {
                return ['error' => 'ليس لديك صلاحية لإنشاء فعاليات في هذا النادي.'];
            }
        }

        $title = trim((string) ($request['title'] ?? ''));

        if ($title === '') {
            return ['error' => 'العنوان مطلوب.'];
        }

        try {
            $startsAt = Carbon::parse($request['starts_at']);
        } catch (\Throwable) {
            return ['error' => 'تاريخ البداية غير صالح.'];
        }

        $endsAt = null;

        if (! empty($request['ends_at'])) {
            try {
                $endsAt = Carbon::parse($request['ends_at']);
            } catch (\Throwable) {
                return ['error' => 'تاريخ النهاية غير صالح.'];
            }
        }

        $location = $request['location'] ?? null;
        $capacity = $request['capacity'] ?? null;
        $description = $request['description'] ?? null;
        $status = $request['status'] ?? EventStatus::Active->value;

        $changes = ["إنشاء فعالية جديدة: \"{$title}\""];
        $changes[] = 'تاريخ البداية: '.$startsAt->translatedFormat('d F Y H:i');

        if ($location) {
            $changes[] = "الموقع: {$location}";
        }

        if ($capacity) {
            $changes[] = "السعة: {$capacity} مقعدًا";
        }

        return [
            'summary' => "إنشاء فعالية \"{$title}\" في نادي {$club->name}",
            'changes' => $changes,
            'params' => [
                'club_id' => $club->id,
                'committee_id' => $committee?->id,
                'title' => $title,
                'description' => $description,
                'starts_at' => $startsAt->toIso8601String(),
                'ends_at' => $endsAt?->toIso8601String(),
                'location' => $location,
                'capacity' => $capacity,
                'status' => $status,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $club = Club::findOrFail($params['club_id']);

        $committee = isset($params['committee_id']) && $params['committee_id'] !== null
            ? Committee::findOrFail($params['committee_id'])
            : null;

        if ($committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageEvents->value, $committee)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لإنشاء فعاليات في هذا النادي.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $club)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لإنشاء فعاليات في هذا النادي.'];
            }
        }

        $event = Event::create([
            'title' => $params['title'],
            'description' => $params['description'],
            'club_id' => $params['club_id'],
            'committee_id' => $params['committee_id'],
            'starts_at' => Carbon::parse($params['starts_at']),
            'ends_at' => isset($params['ends_at']) ? Carbon::parse($params['ends_at']) : null,
            'location' => $params['location'],
            'capacity' => $params['capacity'],
            'status' => $params['status'] ?? EventStatus::Active->value,
        ]);

        return [
            'success' => true,
            'message' => "تم إنشاء فعالية \"{$event->title}\" بنجاح. يمكنك إضافة الصور عبر صفحة الإدارة.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('Club name or ID.')
                ->required(),
            'committee' => $schema->string()
                ->description('Committee name or ID to scope the event to a committee.'),
            'title' => $schema->string()
                ->description('Event title.')
                ->required(),
            'description' => $schema->string()
                ->description('Event description.'),
            'starts_at' => $schema->string()
                ->description('ISO 8601 start datetime (e.g. 2026-06-15T09:00:00).')
                ->required(),
            'ends_at' => $schema->string()
                ->description('ISO 8601 end datetime.'),
            'location' => $schema->string()
                ->description('Event location.'),
            'capacity' => $schema->integer()
                ->description('Maximum attendee capacity.'),
            'status' => $schema->string()
                ->description('Event status: active or cancelled (default: active).'),
        ];
    }
}
