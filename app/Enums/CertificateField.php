<?php

namespace App\Enums;

/**
 * The catalog of data attributes a certificate-template placeholder can be
 * bound to. Each case resolves to a concrete value (text or image) at render
 * time from the issuing EventAttendance and its related models.
 */
enum CertificateField: string
{
    // Recipient identity
    case RecipientName = 'recipient_name';
    case RecipientEmail = 'recipient_email';
    case RecipientUniversityId = 'recipient_university_id';

    // Event / activity
    case EventTitle = 'event_title';
    case EventDate = 'event_date';
    case EventLocation = 'event_location';

    // Club & organisation
    case ClubName = 'club_name';
    case ClubLogo = 'club_logo';
    case UniversityName = 'university_name';
    case PlatformName = 'platform_name';

    // Certificate meta & custom
    case CertificateTitle = 'certificate_title';
    case CertificateDescription = 'certificate_description';
    case VolunteerHours = 'volunteer_hours';
    case IssueDate = 'issue_date';
    case CertificateNumber = 'certificate_number';
    case StaticText = 'static_text';

    /**
     * All field values, for validation `in:` rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $field): string => $field->value, self::cases());
    }

    /**
     * Human-friendly translation key for this field.
     */
    public function label(): string
    {
        return "certificate_fields.{$this->value}";
    }

    /**
     * Whether this field renders as an image rather than text.
     */
    public function isImage(): bool
    {
        return $this === self::ClubLogo;
    }

    /**
     * Whether this field's printed content comes from the placeholder's own
     * `static_text` rather than from resolved record data.
     */
    public function isStatic(): bool
    {
        return $this === self::StaticText;
    }
}
