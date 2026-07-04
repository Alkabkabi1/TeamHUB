<?php

return [
    'title' => 'Events',
    'hero_title' => 'TeamHUB',
    'hero_subtitle' => 'Explore upcoming events, workshops, and team activities in one place',
    'section_aria' => 'Events',
    'search_placeholder' => 'Search by event name or location…',
    'search_aria' => 'Search events',
    'no_events' => 'No events match your search.',
    'sort_options' => [
        'soonest' => 'Soonest first',
        'newest' => 'Recently added',
        'title' => 'Alphabetical',
    ],
    'details_soon' => 'Event details will be added soon.',
    'location_tbd' => 'To be announced',
    'category_general' => 'General',
    'status_labels' => [
        'active' => 'Active',
        'draft' => 'Draft',
        'cancelled' => 'Cancelled',
    ],

    // CRUD flash messages
    'created' => 'Event created successfully.',
    'updated' => 'Event updated successfully.',
    'deleted' => 'Event deleted successfully.',

    // RSVP
    'rsvp_success' => 'You have successfully registered for this event.',
    'rsvp_cancelled' => 'Your registration has been cancelled.',
    'rsvp_capacity_full' => 'Sorry, this event is full.',
    'rsvp_event_not_available' => 'This event is not available for registration.',
    'rsvp_cancel_past_event' => 'You cannot cancel registration for a past event.',
    'rsvp_register' => 'Register',
    'rsvp_registered' => 'Registered',
    'rsvp_cancel' => 'Cancel Registration',
    'capacity_full' => 'Full',
    'details' => 'Details',
    'list_heading' => 'Events list',
    'load_more' => 'Show more',
    'register_now' => 'Register now',
    'cannot_register' => 'Registration closed',
    'availability_open' => 'Registration open',
    'availability_closed' => 'Registration unavailable',

    // Event detail page
    'show' => [
        'back' => 'Back to events',
        'when' => 'Date & time',
        'where' => 'Location',
        'capacity' => 'Registrations',
        'organized_by' => 'Organized by',
        'about' => 'About this event',
        'visit_club' => 'Visit club page',
        'manage' => 'Manage event',
        'edit' => 'Edit event',
        'registration_closed' => 'Registration is closed.',
        'to' => 'to',
    ],

    // Form labels
    'form' => [
        'create_title' => 'Create New Event',
        'edit_title' => 'Edit Event',
        'field_title' => 'Title',
        'field_description' => 'Description',
        'field_starts_at' => 'Start Date & Time',
        'field_ends_at' => 'End Date & Time',
        'field_location' => 'Location',
        'field_capacity' => 'Capacity',
        'field_status' => 'Status',
        'field_images' => 'Images',
        'images_hint' => 'Add up to 10 images. JPEG, PNG or WebP, max 10 MB each.',
        'add_images' => 'Add images',
        'remove_image' => 'Remove image',
        'submit_create' => 'Create Event',
        'submit_update' => 'Update Event',
        'cancel' => 'Cancel',
    ],

    // Validation messages
    'validation' => [
        'title' => [
            'required' => 'The event title is required.',
            'max' => 'The event title must not exceed 255 characters.',
        ],
        'starts_at' => [
            'required' => 'The start date is required.',
            'date' => 'The start date must be a valid date.',
        ],
        'ends_at' => [
            'required' => 'The end date is required.',
            'date' => 'The end date must be a valid date.',
            'after_or_equal' => 'The end date must be on or after the start date.',
        ],
        'capacity' => [
            'integer' => 'Capacity must be a whole number.',
            'min' => 'Capacity must be at least 1.',
        ],
        'status' => [
            'in' => 'The selected status is invalid.',
        ],
        'images' => [
            'image' => 'Each uploaded file must be an image.',
            'mimes' => 'Images must be JPEG, JPG, PNG or WebP files.',
            'size' => 'Each image must not exceed 10 MB.',
            'max' => 'You can upload at most 10 images.',
        ],
    ],

    // Reminder notification
    'reminder' => [
        'subject' => 'Reminder: :title is tomorrow',
        'greeting' => 'Hello!',
        'body' => 'This is a reminder that ":title" is starting on :date at :location.',
        'action' => 'View Events',
        'footer' => 'Thank you for being part of our university clubs community.',
    ],

    // Sent to the student after a successful RSVP
    'rsvp_confirmation' => [
        'subject' => 'You\'re registered for :title',
        'greeting' => 'Hello :name,',
        'body' => 'Your registration for ":title" is confirmed. It starts on :date at :location.',
        'action' => 'View event',
        'footer' => 'See you there! You can cancel your registration anytime from the event page.',
    ],

    // Sent to registered attendees when an event is cancelled
    'cancelled_notification' => [
        'subject' => 'Cancelled: :title',
        'greeting' => 'Hello :name,',
        'body' => 'We\'re sorry to let you know that ":title" (scheduled for :date) has been cancelled.',
        'action' => 'Browse other events',
        'footer' => 'We apologise for any inconvenience.',
    ],

    // Sent to registered attendees when an event\'s schedule or location changes
    'updated_notification' => [
        'subject' => 'Updated details for :title',
        'greeting' => 'Hello :name,',
        'body' => 'The details for ":title" have changed. It now takes place on :date at :location.',
        'action' => 'View event',
        'footer' => 'Please review the new details so you don\'t miss it.',
    ],

    // Managed events (supervisor dashboard)
    'managed' => [
        'registrations' => ':count registered',
        'registrations_of' => ':count of :total registered',
        'no_capacity' => 'Unlimited',
    ],
];
