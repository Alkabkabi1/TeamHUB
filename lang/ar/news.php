<?php

return [
    // Public feed / article pages
    'title' => 'الأخبار',
    'hero_title' => 'آخر الأخبار',
    'hero_subtitle' => 'تابع آخر التحديثات والإعلانات من الأندية.',
    'search_placeholder' => 'ابحث في الأخبار',
    'search_aria' => 'ابحث في الأخبار',
    'sort_options' => [
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
        'title' => 'أبجديًا',
    ],
    'empty' => 'لا توجد أخبار بعد.',
    'read_more' => 'اقرأ المزيد',
    'back_to_news' => 'العودة إلى الأخبار',
    'by_author' => 'بقلم :name',
    'related' => 'المزيد من هذا النادي',

    // Form labels
    'form' => [
        'create_title' => 'نشر خبر',
        'edit_title' => 'تعديل الخبر',
        'title' => 'العنوان',
        'body' => 'المحتوى',
        'image' => 'الصور',
        'current_image' => 'الصورة الحالية',
        'replace_image_hint' => 'رفع صورة جديدة سيستبدل الصورة الحالية.',
        'images_hint' => 'أضف حتى 10 صور بصيغة JPEG أو PNG أو WebP، بحد أقصى 10 ميجابايت لكل صورة.',
        'add_images' => 'إضافة صور',
        'remove_image' => 'إزالة الصورة',
        'submit' => 'نشر',
        'submit_update' => 'حفظ التغييرات',
        'cancel' => 'إلغاء',
    ],

    // Validation messages
    'validation' => [
        'title' => [
            'required' => 'عنوان الخبر مطلوب.',
        ],
        'body' => [
            'required' => 'محتوى الخبر مطلوب.',
        ],
        'image' => [
            'image' => 'يجب أن يكون كل ملف مرفوع صورة.',
            'mimes' => 'يجب أن تكون الصور بصيغة JPEG أو JPG أو PNG أو WebP.',
            'max' => 'يجب ألا تتجاوز كل صورة 10 ميجابايت.',
            'count' => 'يمكنك رفع 10 صور كحد أقصى.',
        ],
    ],

    // Flash messages
    'created' => 'تم نشر الخبر بنجاح.',
    'updated' => 'تم تحديث الخبر بنجاح.',
    'deleted' => 'تم حذف الخبر.',

    // Notification
    'notification' => [
        'subject' => 'خبر جديد من :club — :title',
        'greeting' => 'مرحباً!',
        'body' => 'تم نشر خبر جديد بعنوان ":title" من ناديك ":club".',
        'action' => 'قراءة الخبر',
        'footer' => 'تصلك هذه الرسالة لأنك عضو في :club.',
    ],
];
