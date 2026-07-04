<?php

return [
    'generated' => 'Certificate generated successfully.',
    'already_exists' => 'Certificate already exists and has been re-generated.',
    'not_eligible' => 'A certificate can only be issued for a checked-in or approved attendance.',
    'event_not_ended' => 'A certificate can only be issued after the event has ended.',
    'no_template' => 'Set an active default certificate template for this club before issuing certificates.',
    'download_not_found' => 'Certificate file not found.',

    // PDF content
    'certificate_of_participation' => 'Certificate of Participation',
    'this_is_to_certify' => 'This is to certify that',
    'has_participated' => 'has participated in',
    'organized_by' => 'Organized by',
    'volunteer_hours' => 'Volunteer hours',
    'hours_unit' => 'hr',
    'issue_date' => 'Issue date',
    'certificate_no' => 'Certificate No.',
    'university_name' => 'TeamHUB',
    'clubs_platform' => 'TeamHUB',
    'participation_role' => 'Participant',
    'footer' => 'TeamHUB',

    // Certificate issued notification (sent to the student)
    'notification' => [
        'subject' => 'Your certificate for :event is ready',
        'greeting' => 'Hello :name,',
        'body' => 'A certificate of participation for ":event" with :club has been issued for you. You can download it anytime from your dashboard.',
        'action' => 'Download certificate',
        'footer' => 'Keep up the great work and thank you for volunteering.',
    ],
];
