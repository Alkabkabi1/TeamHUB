<?php

return [
    // Public feed / article pages
    'title' => 'News',
    'hero_title' => 'Latest news',
    'hero_subtitle' => 'Follow the latest updates and announcements from the clubs.',
    'search_placeholder' => 'Search news',
    'search_aria' => 'Search news',
    'sort_options' => [
        'newest' => 'Newest',
        'oldest' => 'Oldest',
        'title' => 'Alphabetical',
    ],
    'empty' => 'There is no news yet.',
    'read_more' => 'Read more',
    'back_to_news' => 'Back to news',
    'by_author' => 'By :name',
    'related' => 'More from this club',

    // Form labels
    'form' => [
        'create_title' => 'Post News',
        'edit_title' => 'Edit News',
        'title' => 'Title',
        'body' => 'Content',
        'image' => 'Images',
        'current_image' => 'Current image',
        'replace_image_hint' => 'Uploading a new image replaces the current one.',
        'images_hint' => 'Add up to 10 images. JPEG, PNG or WebP, max 10 MB each.',
        'add_images' => 'Add images',
        'remove_image' => 'Remove image',
        'submit' => 'Publish',
        'submit_update' => 'Save changes',
        'cancel' => 'Cancel',
    ],

    // Validation messages
    'validation' => [
        'title' => [
            'required' => 'The post title is required.',
        ],
        'body' => [
            'required' => 'The post content is required.',
        ],
        'image' => [
            'image' => 'Each uploaded file must be an image.',
            'mimes' => 'Images must be JPEG, JPG, PNG or WebP files.',
            'max' => 'Each image must not exceed 10 MB.',
            'count' => 'You can upload at most 10 images.',
        ],
    ],

    // Flash messages
    'created' => 'News post published successfully.',
    'updated' => 'News post updated successfully.',
    'deleted' => 'News post deleted.',

    // Notification
    'notification' => [
        'subject' => 'New post from :club — :title',
        'greeting' => 'Hello!',
        'body' => 'A new post titled ":title" has been published by your club ":club".',
        'action' => 'Read the post',
        'footer' => 'You receive this because you are a member of :club.',
    ],
];
