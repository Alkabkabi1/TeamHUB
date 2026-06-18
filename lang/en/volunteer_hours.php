<?php

return [
    'recorded' => 'Volunteer hours recorded successfully.',
    'validation' => [
        'user_id' => [
            'required' => 'Please select a student.',
            'exists' => 'The selected student does not exist.',
            'no_attendance' => 'The student has no attendance record for this event.',
            'not_member' => 'The selected student is not an approved member of this club.',
        ],
        'event_id' => [
            'required' => 'Please select an event.',
            'exists' => 'The selected event does not exist.',
            'wrong_club' => 'This event does not belong to your club.',
            'not_finished' => 'Cannot record hours for an event that has not ended yet.',
        ],
        'hours' => [
            'required' => 'Hours are required.',
            'numeric' => 'Hours must be a number.',
            'min' => 'Hours must be at least 0.',
            'max' => 'Hours cannot exceed 100.',
        ],
    ],
];
