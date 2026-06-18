<?php

return [
    // Scanner page (club Attendance Scanner)
    'scan' => [
        'title' => 'Attendance scanner',
        'subtitle' => 'Scan a student\'s QR code to log their attendance for today.',
        'back' => 'Back to dashboard',
        'start_camera' => 'Start camera',
        'stop_camera' => 'Stop camera',
        'camera_hint' => 'Point the camera at the student\'s attendance QR code.',
        'camera_error' => 'Could not access the camera. Check the browser permissions and try again.',
        'today' => 'Today',
        'checked_in_today_count' => ':count checked in today',
        'checked_in_today' => 'Checked in today',
        'not_checked_in' => 'Not checked in today',
        'days_attended' => ':count days',
        'roster_title' => 'Registered students',
        'roster_empty' => 'No students have registered for this activity yet.',
        'result' => [
            'checked_in' => ':name has been checked in.',
            'already_today' => ':name was already checked in today.',
            'walk_in' => ':name was checked in as a walk-in (not pre-registered).',
            'invalid' => 'Unrecognized QR code.',
        ],
    ],

    // Attendance action on the club management dashboard's event cards
    'manage' => [
        'scan_button' => 'Scan attendance',
    ],
];
