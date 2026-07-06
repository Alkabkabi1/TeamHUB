<?php

return [
    'title' => 'Join application',
    'submitted' => 'Your application was submitted successfully. A workspace lead will review it.',
    'full_name' => 'Full name',
    'university_email' => 'Email',
    'phone' => 'Phone number',
    'level' => 'Academic level',
    'major' => 'Major',
    'skills' => 'Skills',
    'weekly_hours' => 'Weekly hours available',
    'tools' => 'Tools & software',
    'motivation' => 'Why do you want to join?',
    'contribution' => 'How will you contribute?',
    'submit' => 'Submit application',
    'join_workspace' => 'Join workspace',
    'placeholder' => [
        'full_name' => 'e.g. Ahmed Mohammed Ahmed',
        'university_email' => 'name@example.com',
        'phone' => '05000000000',
        'level' => 'e.g. Level 8',
        'major' => 'e.g. Software Engineering',
        'skills' => 'e.g. Graphic design, public speaking',
        'weekly_hours' => 'e.g. 4',
        'tools' => 'e.g. Photoshop, Canva, Office, Notion',
        'motivation' => 'Tell us why this workspace fits you',
        'contribution' => 'What value will you add to the workspace?',
    ],
    'validation' => [
        'full_name' => [
            'required' => 'Student name is required.',
        ],
        'university_email' => [
            'required' => 'Email is required.',
            'email' => 'Email is not valid.',
            'mismatch' => 'Email must match your logged-in account email.',
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
        'workspace' => [
            'inactive' => 'This workspace is not accepting applications right now.',
            'already_member' => 'You are already a member of this workspace.',
            'pending_application' => 'You already have a pending application for this workspace.',
        ],
    ],

    'notification' => [
        'approved' => [
            'subject' => 'Your membership in :workspace has been approved',
            'greeting' => 'Congratulations, :name!',
            'body' => 'Your request to join ":workspace" has been approved. You are now a member and can take part in its activities.',
            'action' => 'Visit the workspace',
            'footer' => 'Welcome to the TeamHUB community.',
        ],
        'rejected' => [
            'subject' => 'Update on your membership request for :workspace',
            'greeting' => 'Hello :name,',
            'body' => 'We\'re sorry, but your request to join ":workspace" was not approved this time. You\'re welcome to explore other workspaces or apply again later.',
            'action' => 'Browse workspaces',
            'footer' => 'Thank you for your interest in TeamHUB.',
        ],
        'received' => [
            'subject' => 'New membership application for :workspace',
            'greeting' => 'Hello :name,',
            'body' => ':applicant has applied to join ":workspace". Please review their application when you have a moment.',
            'action' => 'Review application',
            'footer' => 'You receive this because you manage members for :workspace.',
        ],
    ],
];
