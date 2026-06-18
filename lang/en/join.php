<?php

return [
    'title' => 'Join application',
    'submitted' => 'Your application was submitted successfully. A club supervisor will review it.',
    'full_name' => 'Full name',
    'university_email' => 'University email',
    'phone' => 'Phone number',
    'level' => 'Academic level',
    'major' => 'Major',
    'skills' => 'Skills',
    'weekly_hours' => 'Weekly hours available',
    'tools' => 'Tools & software',
    'motivation' => 'Why do you want to join?',
    'contribution' => 'How will you contribute?',
    'submit' => 'Submit application',
    'join_club' => 'Join club',
    'placeholder' => [
        'full_name' => 'e.g. Ahmed Mohammed Ahmed',
        'university_email' => 'Student@uqu.edu.sa',
        'phone' => '05000000000',
        'level' => 'e.g. Level 8',
        'major' => 'e.g. Software Engineering',
        'skills' => 'e.g. Graphic design, public speaking',
        'weekly_hours' => 'e.g. 4',
        'tools' => 'e.g. Photoshop, Canva, Office, Notion',
        'motivation' => 'Tell us why this club fits you',
        'contribution' => 'What value will you add to the club?',
    ],
    'validation' => [
        'full_name' => [
            'required' => 'Student name is required.',
        ],
        'university_email' => [
            'required' => 'University email is required.',
            'email' => 'University email is not valid.',
            'ends_with' => 'You must use your university email (@uqu.edu.sa).',
            'mismatch' => 'University email must match your logged-in account email.',
        ],
        'phone' => [
            'required' => 'Phone number is required.',
        ],
        'level' => [
            'required' => 'Academic level is required.',
        ],
        'major' => [
            'required' => 'Major is required.',
        ],
        'skills' => [
            'required' => 'Please describe your skills.',
        ],
        'weekly_hours' => [
            'required' => 'Please specify weekly hours.',
        ],
        'tools' => [
            'required' => 'Please list tools or software.',
        ],
        'motivation' => [
            'required' => 'Please explain why you want to join.',
        ],
        'contribution' => [
            'required' => 'Please describe your contribution.',
        ],
        'club' => [
            'inactive' => 'This club is not accepting applications right now.',
            'already_member' => 'You are already a member of this club.',
            'pending_application' => 'You already have a pending application for this club.',
        ],
    ],

    // Membership decision notifications (sent to the applicant)
    'notification' => [
        'approved' => [
            'subject' => 'Your membership in :club has been approved',
            'greeting' => 'Congratulations, :name!',
            'body' => 'Your request to join ":club" has been approved. You are now a member and can take part in the club\'s events and activities.',
            'action' => 'Visit the club',
            'footer' => 'Welcome to the club community on Ruwad Al-Andiyah.',
        ],
        'rejected' => [
            'subject' => 'Update on your membership request for :club',
            'greeting' => 'Hello :name,',
            'body' => 'We\'re sorry, but your request to join ":club" was not approved this time. You\'re welcome to explore other clubs or apply again later.',
            'action' => 'Browse clubs',
            'footer' => 'Thank you for your interest in our university clubs.',
        ],
        'received' => [
            'subject' => 'New membership application for :club',
            'greeting' => 'Hello :name,',
            'body' => ':applicant has applied to join ":club". Please review their application when you have a moment.',
            'action' => 'Review application',
            'footer' => 'You receive this because you manage members for :club.',
        ],
    ],
];
