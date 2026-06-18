<?php

namespace App\Ai\Tools;

use App\Models\Certificate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * The certificates the current user has earned.
 */
class GetMyCertificates extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the certificates the current user has been issued, including the related event and '
            .'issue date. Use for "what certificates do I have?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $certificates = $this->user->certificates()
            ->with('attendance.event:id,title')
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $certificate): array => [
                'certificateNo' => $certificate->certificate_no,
                'event' => $certificate->attendance?->event?->title,
                'issuedAt' => $certificate->issued_at?->toIso8601String(),
                'downloadUrl' => route('certificates.download', $certificate),
            ])
            ->all();

        return $this->json(['certificates' => $certificates]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
