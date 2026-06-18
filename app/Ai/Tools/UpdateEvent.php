<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Update an existing event's text fields using the two-phase confirm flow.
 */
class UpdateEvent extends WriteTool
{
    public function description(): Stringable|string
    {
        return "Update an existing event's text fields. Images cannot be changed via chat.";
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
                return ['error' => 'ليس لديك صلاحية لتعديل هذه الفعالية.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $authTarget)) {
                return ['error' => 'ليس لديك صلاحية لتعديل هذه الفعالية.'];
            }
        }

        $changes = [];
        $params = ['event_id' => $event->id];

        if (array_key_exists('title', $request->all()) && $request['title'] !== $event->title) {
            $changes[] = "العنوان: \"{$event->title}\" → \"{$request['title']}\"";
            $params['title'] = $request['title'];
        }

        if (array_key_exists('description', $request->all()) && $request['description'] !== $event->description) {
            $changes[] = 'الوصف: تم تعديله';
            $params['description'] = $request['description'];
        }

        if (array_key_exists('starts_at', $request->all())) {
            try {
                $newStartsAt = Carbon::parse($request['starts_at']);
                $currentStartsAt = $event->starts_at?->toIso8601String();
                if ($newStartsAt->toIso8601String() !== $currentStartsAt) {
                    $changes[] = 'تاريخ البداية: '.$event->starts_at?->translatedFormat('d F Y H:i').' → '.$newStartsAt->translatedFormat('d F Y H:i');
                    $params['starts_at'] = $newStartsAt->toIso8601String();
                }
            } catch (\Throwable) {
                return ['error' => 'تاريخ البداية غير صالح.'];
            }
        }

        if (array_key_exists('ends_at', $request->all())) {
            try {
                $newEndsAt = Carbon::parse($request['ends_at']);
                $currentEndsAt = $event->ends_at?->toIso8601String();
                if ($newEndsAt->toIso8601String() !== $currentEndsAt) {
                    $changes[] = 'تاريخ النهاية: '.($event->ends_at?->translatedFormat('d F Y H:i') ?? 'غير محدد').' → '.$newEndsAt->translatedFormat('d F Y H:i');
                    $params['ends_at'] = $newEndsAt->toIso8601String();
                }
            } catch (\Throwable) {
                return ['error' => 'تاريخ النهاية غير صالح.'];
            }
        }

        if (array_key_exists('location', $request->all()) && $request['location'] !== $event->location) {
            $changes[] = 'الموقع: '.($event->location ?? 'غير محدد')." → \"{$request['location']}\"";
            $params['location'] = $request['location'];
        }

        if (array_key_exists('capacity', $request->all()) && $request['capacity'] !== $event->capacity) {
            $changes[] = 'السعة: '.($event->capacity ?? 'غير محدد')." → {$request['capacity']}";
            $params['capacity'] = $request['capacity'];
        }

        if (array_key_exists('status', $request->all()) && $request['status'] !== $event->status?->value) {
            $changes[] = "الحالة: {$event->status?->value} → {$request['status']}";
            $params['status'] = $request['status'];
        }

        if (empty($changes)) {
            return ['error' => 'لم يتم تحديد أي تعديلات.'];
        }

        return [
            'summary' => "تعديل فعالية \"{$event->title}\"",
            'changes' => $changes,
            'params' => $params,
        ];
    }

    public function execute(array $params): array
    {
        $event = Event::with('club', 'committee')->findOrFail($params['event_id']);
        $authTarget = $event->committee ?? $event->club;

        if ($event->committee !== null) {
            if (! Gate::allows(CommitteeCapability::ManageEvents->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لتعديل هذه الفعالية.'];
            }
        } else {
            if (! Gate::allows(ClubCapability::ManageEvents->value, $authTarget)) {
                return ['success' => false, 'message' => 'ليس لديك صلاحية لتعديل هذه الفعالية.'];
            }
        }

        $data = [];

        foreach (['title', 'description', 'location', 'capacity', 'status'] as $field) {
            if (array_key_exists($field, $params)) {
                $data[$field] = $params[$field];
            }
        }

        if (array_key_exists('starts_at', $params)) {
            $data['starts_at'] = Carbon::parse($params['starts_at']);
        }

        if (array_key_exists('ends_at', $params)) {
            $data['ends_at'] = Carbon::parse($params['ends_at']);
        }

        $event->update($data);

        return [
            'success' => true,
            'message' => "تم تعديل فعالية \"{$event->title}\" بنجاح.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()
                ->description('Event name or numeric ID.')
                ->required(),
            'title' => $schema->string()
                ->description('New title.'),
            'description' => $schema->string()
                ->description('New description.'),
            'starts_at' => $schema->string()
                ->description('New ISO 8601 start datetime.'),
            'ends_at' => $schema->string()
                ->description('New ISO 8601 end datetime.'),
            'location' => $schema->string()
                ->description('New location.'),
            'capacity' => $schema->integer()
                ->description('New capacity.'),
            'status' => $schema->string()
                ->description('New status: active or cancelled.'),
        ];
    }
}
