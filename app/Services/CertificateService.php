<?php

namespace App\Services;

use App\Enums\CertificateField;
use App\Models\Certificate;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CertificateService
{
    /**
     * Issue (or regenerate) a certificate for a user. Activity-based when an
     * event is given, otherwise a standalone certificate carrying overrides.
     * Renders from the given template, falling back to the club's default.
     *
     * @param  array{title?: string|null, description?: string|null, volunteer_hours?: float|int|string|null, issued_at?: \DateTimeInterface|string|null}  $overrides
     */
    public function issue(
        User $user,
        Club $club,
        ?Event $event = null,
        array $overrides = [],
        ?EventAttendance $attendance = null,
        ?CertificateTemplate $template = null,
    ): Certificate {
        $template ??= $club->defaultCertificateTemplate();

        if ($template === null) {
            throw new RuntimeException('The club has no active default certificate template.');
        }

        // One certificate per (user, event) when activity-based; standalone
        // certificates (no event) are always created fresh so a student may
        // hold several distinct manual certificates.
        $certificate = $event !== null
            ? Certificate::firstOrNew(['user_id' => $user->id, 'event_id' => $event->id])
            : new Certificate(['user_id' => $user->id]);

        $certificate->fill([
            'club_id' => $club->id,
            'certificate_template_id' => $template->id,
            'event_id' => $event?->id,
            'event_attendance_id' => $attendance?->id ?? $certificate->event_attendance_id,
            'title' => $overrides['title'] ?? $certificate->title,
            'description' => $overrides['description'] ?? $certificate->description,
            'volunteer_hours' => $overrides['volunteer_hours'] ?? $certificate->volunteer_hours,
        ]);

        if (! empty($overrides['issued_at'])) {
            $certificate->issued_at = $overrides['issued_at'];
        }

        // Persist first so the booted hook assigns certificate_no / issued_at,
        // which the rendered PDF prints.
        if (! $certificate->exists || $certificate->isDirty()) {
            $certificate->file_path ??= 'certificates/pending.pdf';
            $certificate->save();
        }

        $certificate->file_path = $this->generateAndStore($certificate);
        $certificate->save();

        return $certificate;
    }

    /**
     * Resolve the concrete value for every bindable field from a certificate.
     *
     * @return array<string, string>
     */
    public function resolveValues(Certificate $certificate): array
    {
        $certificate->loadMissing(['user', 'club', 'event']);

        $event = $certificate->event;
        $club = $certificate->club;
        $user = $certificate->user;
        $locale = app()->getLocale();

        $hours = $certificate->volunteer_hours !== null
            ? (float) $certificate->volunteer_hours
            : $this->resolvedHours($certificate);

        return [
            CertificateField::RecipientName->value => $user?->name ?? '',
            CertificateField::RecipientEmail->value => $user?->email ?? '',
            CertificateField::RecipientUniversityId->value => (string) ($user?->university_id ?? ''),
            CertificateField::EventTitle->value => $event?->title ?? ($certificate->title ?? ''),
            CertificateField::EventDate->value => $event?->starts_at?->locale($locale)->translatedFormat('d F Y') ?? '',
            CertificateField::EventLocation->value => $event?->location ?? '',
            CertificateField::ClubName->value => $club?->name ?? '',
            CertificateField::UniversityName->value => __('certificates.university_name'),
            CertificateField::PlatformName->value => __('certificates.clubs_platform'),
            CertificateField::CertificateTitle->value => $certificate->title ?? ($event?->title ?? ''),
            CertificateField::CertificateDescription->value => $certificate->description ?? '',
            CertificateField::VolunteerHours->value => $this->formatHours($hours),
            CertificateField::IssueDate->value => ($certificate->issued_at ?? now())->locale($locale)->translatedFormat('d F Y'),
            CertificateField::CertificateNumber->value => $certificate->certificate_no ?? '',
        ];
    }

    /**
     * Render a certificate PDF from a template and a resolved value map.
     *
     * @param  array<string, string>  $values
     */
    public function renderPdf(CertificateTemplate $template, array $values): PdfInstance
    {
        $template->loadMissing('placeholders', 'club');

        $width = max(1, (int) $template->width);
        $height = max(1, (int) $template->height);

        $layers = $template->placeholders
            ->map(fn (CertificatePlaceholder $placeholder): array => $this->buildLayer($placeholder, $values, $template, $width, $height))
            ->all();

        $data = [
            'backgroundImage' => $this->dataUri($template->imagePath()),
            'width' => $width,
            'height' => $height,
            'layers' => $layers,
        ];

        // px -> pt at 96 DPI (1px = 0.75pt) so on-page coordinates match the editor.
        $paper = [0, 0, $width * 0.75, $height * 0.75];

        return Pdf::loadView('certificates.template', $data)->setPaper($paper);
    }

    /**
     * Generate and store a certificate PDF, returning the stored file path.
     */
    public function generateAndStore(Certificate $certificate): string
    {
        $template = $this->templateFor($certificate);
        $values = $this->resolveValues($certificate);

        $filename = "certificates/{$certificate->certificate_no}.pdf";
        Storage::disk('public')->put($filename, $this->renderPdf($template, $values)->output());

        return $filename;
    }

    /**
     * Re-generate the PDF bytes for on-the-fly download (file missing scenario).
     */
    public function regenerateBytes(Certificate $certificate): string
    {
        $template = $this->templateFor($certificate);
        $values = $this->resolveValues($certificate);

        return $this->renderPdf($template, $values)->output();
    }

    /**
     * Render a sample PDF for the designer preview, using placeholder labels as
     * stand-in values so positions and styling can be checked without data.
     */
    public function renderPreviewBytes(CertificateTemplate $template): string
    {
        $template->loadMissing('placeholders', 'club');

        $values = [];
        foreach (CertificateField::cases() as $field) {
            $values[$field->value] = __($field->label());
        }

        return $this->renderPdf($template, $values)->output();
    }

    /**
     * Resolve the template a certificate renders from: the one it was issued
     * with when set, otherwise the club's active default. Fails when neither.
     */
    private function templateFor(Certificate $certificate): CertificateTemplate
    {
        $certificate->loadMissing('certificateTemplate');

        $template = $certificate->certificateTemplate
            ?? $certificate->club?->defaultCertificateTemplate();

        if (! $template instanceof CertificateTemplate) {
            throw new RuntimeException('The club has no active default certificate template.');
        }

        return $template;
    }

    /**
     * Sum the recorded volunteer hours for an activity-based certificate.
     */
    private function resolvedHours(Certificate $certificate): float
    {
        if ($certificate->user_id === null || $certificate->event_id === null) {
            return 0.0;
        }

        return (float) VolunteerHour::query()
            ->where('user_id', $certificate->user_id)
            ->where('event_id', $certificate->event_id)
            ->sum('hours');
    }

    /**
     * Build a single render-ready layer (absolute position + resolved content).
     *
     * @param  array<string, string>  $values
     * @return array<string, mixed>
     */
    private function buildLayer(
        CertificatePlaceholder $placeholder,
        array $values,
        CertificateTemplate $template,
        int $width,
        int $height,
    ): array {
        $field = $placeholder->binding;
        $isImage = $field->isImage();

        if ($field->isStatic()) {
            $content = ArabicText::forPdf($placeholder->static_text);
        } elseif ($isImage) {
            $content = $this->dataUri($template->club?->logoPath());
        } else {
            $content = ArabicText::forPdf($values[$field->value] ?? '');
        }

        return [
            'isImage' => $isImage,
            'content' => $content,
            'left' => $placeholder->x * $width,
            'top' => $placeholder->y * $height,
            'width' => $placeholder->width * $width,
            'fontSize' => $placeholder->font_size * $height,
            'fontFamily' => $placeholder->font_family,
            'fontWeight' => $placeholder->font_weight,
            'color' => $placeholder->color,
            'align' => $placeholder->align,
        ];
    }

    /**
     * Read a local image file into a base64 data URI, or null when absent.
     */
    private function dataUri(?string $path): ?string
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * Format an hours total, dropping a trailing ".0".
     */
    private function formatHours(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');
    }
}
