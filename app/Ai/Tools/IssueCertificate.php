<?php

namespace App\Ai\Tools;

use App\Enums\ClubCapability;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Services\CertificateService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

/**
 * Issue a certificate to a student for a specific event attendance or manually. Requires IssueCertificates capability.
 * The club must have a default certificate template configured.
 */
class IssueCertificate extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Issue a certificate to a student for a specific event attendance or manually. Requires IssueCertificates capability. The club must have a default certificate template configured.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'club' => $schema->string()
                ->description('Club name or numeric ID.')
                ->required(),
            'user' => $schema->string()
                ->description('Student name or numeric ID.')
                ->required(),
            'event' => $schema->string()
                ->description('Event name or numeric ID to tie the certificate to.'),
            'title' => $schema->string()
                ->description('Custom certificate title override.'),
            'description' => $schema->string()
                ->description('Custom description override.'),
            'volunteer_hours' => $schema->number()
                ->description('Volunteer hours override.'),
            'issued_at' => $schema->string()
                ->description('Custom issue date (YYYY-MM-DD).'),
        ];
    }

    protected function preview(Request $request): array
    {
        $club = $this->resolveClub($request['club']);

        if ($club === null) {
            return ['error' => 'لم يتم العثور على النادي.'];
        }

        if (! Gate::allows(ClubCapability::IssueCertificates->value, $club)) {
            return ['error' => 'ليس لديك صلاحية لإصدار الشهادات في هذا النادي.'];
        }

        $targetUser = $this->resolveUser($request['user'] ?? null, $club);

        if ($targetUser === null) {
            return ['error' => 'لم يتم العثور على عضو بهذا الاسم في النادي.'];
        }

        if ($club->defaultCertificateTemplate() === null) {
            return ['error' => 'لا يوجد قالب شهادة افتراضي لهذا النادي. يرجى إعداد القالب أولاً من صفحة الإدارة.'];
        }

        $resolvedEvent = isset($request['event']) && $request['event'] !== ''
            ? $this->resolveEvent($request['event'], $club)
            : null;
        $title = $request['title'] ?? null;
        $description = $request['description'] ?? null;
        $volunteerHours = $request['volunteer_hours'] ?? null;
        $issuedAt = $request['issued_at'] ?? null;

        $changes = ["إصدار شهادة لـ {$targetUser->name} من نادي {$club->name}"];

        if ($resolvedEvent) {
            $changes[] = "مرتبطة بالفعالية \"{$resolvedEvent->title}\"";
        }

        if ($title) {
            $changes[] = "عنوان مخصص: \"{$title}\"";
        }

        return [
            'summary' => "إصدار شهادة لـ {$targetUser->name} من نادي {$club->name}",
            'changes' => $changes,
            'params' => [
                'club_id' => $club->id,
                'user_id' => $targetUser->id,
                'event_id' => $resolvedEvent?->id,
                'title' => $title,
                'description' => $description,
                'volunteer_hours' => $volunteerHours,
                'issued_at' => $issuedAt,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $club = $this->resolveClub($params['club_id']);

        abort_unless(Gate::allows(ClubCapability::IssueCertificates->value, $club), 403);

        if ($club->defaultCertificateTemplate() === null) {
            return ['success' => false, 'message' => 'لا يوجد قالب شهادة افتراضي لهذا النادي.'];
        }

        $user = User::findOrFail($params['user_id']);

        $event = Event::find($params['event_id']);

        $attendance = null;

        if ($event !== null) {
            $attendance = EventAttendance::where('user_id', $user->id)->where('event_id', $event->id)->first();
        }

        try {
            $certificate = app(CertificateService::class)->issue(
                user: $user,
                club: $club,
                event: $event,
                overrides: [
                    'title' => $params['title'] ?? null,
                    'description' => $params['description'] ?? null,
                    'volunteer_hours' => $params['volunteer_hours'] ?? null,
                    'issued_at' => $params['issued_at'] ?? null,
                ],
                attendance: $attendance,
            );
        } catch (RuntimeException) {
            return ['success' => false, 'message' => 'حدث خطأ أثناء إصدار الشهادة.'];
        }

        if ($certificate->wasRecentlyCreated) {
            $user->notify(new CertificateIssuedNotification($certificate));
        }

        return [
            'success' => true,
            'message' => "تم إصدار الشهادة لـ {$user->name} بنجاح.",
        ];
    }
}
