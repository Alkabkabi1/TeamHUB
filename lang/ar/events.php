<?php

return [
    'title' => 'الفعاليات',
    'hero_title' => 'TeamHUB',
    'hero_subtitle' => 'استكشف الفعاليات القادمة وورش العمل وأنشطة الفريق في مكان واحد',
    'section_aria' => 'الفعاليات',
    'search_placeholder' => 'ابحث باسم الفعالية أو الموقع...',
    'search_aria' => 'البحث عن الفعاليات',
    'no_events' => 'لا توجد فعاليات مطابقة.',
    'sort_options' => [
        'soonest' => 'الأقرب موعداً',
        'newest' => 'المضافة حديثاً',
        'title' => 'أبجديًا',
    ],
    'details_soon' => 'تفاصيل الفعالية ستضاف قريباً.',
    'location_tbd' => 'يحدد لاحقاً',
    'category_general' => 'عام',
    'status_labels' => [
        'active' => 'نشط',
        'draft' => 'مسودة',
        'cancelled' => 'ملغي',
    ],

    // CRUD flash messages
    'created' => 'تم إنشاء الفعالية بنجاح.',
    'updated' => 'تم تحديث الفعالية بنجاح.',
    'deleted' => 'تم حذف الفعالية بنجاح.',

    // RSVP
    'rsvp_success' => 'تم تسجيلك في الفعالية بنجاح.',
    'rsvp_cancelled' => 'تم إلغاء تسجيلك.',
    'rsvp_capacity_full' => 'عذراً، اكتمل عدد المسجلين في هذه الفعالية.',
    'rsvp_event_not_available' => 'هذه الفعالية غير متاحة للتسجيل.',
    'rsvp_cancel_past_event' => 'لا يمكن إلغاء التسجيل في فعالية منتهية.',
    'rsvp_register' => 'التسجيل في الفعالية',
    'rsvp_registered' => 'مسجّل',
    'rsvp_cancel' => 'إلغاء التسجيل',
    'capacity_full' => 'مكتمل',
    'details' => 'التفاصيل',
    'list_heading' => 'قائمة الفعاليات',
    'load_more' => 'عرض المزيد',
    'register_now' => 'سجل الآن',
    'cannot_register' => 'لا يمكن التسجيل',
    'availability_open' => 'التسجيل متاح',
    'availability_closed' => 'التسجيل غير متاح',

    // Event detail page
    'show' => [
        'back' => 'العودة إلى الفعاليات',
        'when' => 'التاريخ والوقت',
        'where' => 'الموقع',
        'capacity' => 'المسجلون',
        'organized_by' => 'تنظيم',
        'about' => 'عن الفعالية',
        'visit_club' => 'زيارة صفحة النادي',
        'manage' => 'إدارة الفعالية',
        'edit' => 'تعديل الفعالية',
        'registration_closed' => 'التسجيل مغلق.',
        'to' => 'إلى',
    ],

    // Form labels
    'form' => [
        'create_title' => 'إنشاء فعالية جديدة',
        'edit_title' => 'تعديل الفعالية',
        'field_title' => 'العنوان',
        'field_description' => 'الوصف',
        'field_starts_at' => 'تاريخ ووقت البداية',
        'field_ends_at' => 'تاريخ ووقت النهاية',
        'field_location' => 'الموقع',
        'field_capacity' => 'السعة',
        'field_status' => 'الحالة',
        'field_images' => 'الصور',
        'images_hint' => 'أضف حتى 10 صور بصيغة JPEG أو PNG أو WebP، بحد أقصى 10 ميجابايت لكل صورة.',
        'add_images' => 'إضافة صور',
        'remove_image' => 'إزالة الصورة',
        'submit_create' => 'إنشاء الفعالية',
        'submit_update' => 'تحديث الفعالية',
        'cancel' => 'إلغاء',
    ],

    // Validation messages
    'validation' => [
        'title' => [
            'required' => 'عنوان الفعالية مطلوب.',
            'max' => 'يجب ألا يتجاوز عنوان الفعالية 255 حرفاً.',
        ],
        'starts_at' => [
            'required' => 'تاريخ البداية مطلوب.',
            'date' => 'يجب أن يكون تاريخ البداية تاريخاً صحيحاً.',
        ],
        'ends_at' => [
            'required' => 'تاريخ النهاية مطلوب.',
            'date' => 'يجب أن يكون تاريخ النهاية تاريخاً صحيحاً.',
            'after_or_equal' => 'يجب أن يكون تاريخ النهاية بعد أو مساوياً لتاريخ البداية.',
        ],
        'capacity' => [
            'integer' => 'يجب أن تكون السعة رقماً صحيحاً.',
            'min' => 'يجب أن تكون السعة على الأقل 1.',
        ],
        'status' => [
            'in' => 'الحالة المحددة غير صالحة.',
        ],
        'images' => [
            'image' => 'يجب أن يكون كل ملف مرفوع صورة.',
            'mimes' => 'يجب أن تكون الصور بصيغة JPEG أو JPG أو PNG أو WebP.',
            'size' => 'يجب ألا تتجاوز كل صورة 10 ميجابايت.',
            'max' => 'يمكنك رفع 10 صور كحد أقصى.',
        ],
    ],

    // Reminder notification
    'reminder' => [
        'subject' => 'تذكير: :title غداً',
        'greeting' => 'مرحباً!',
        'body' => 'هذا تذكير بأن فعالية ":title" ستبدأ بتاريخ :date في :location.',
        'action' => 'عرض الفعاليات',
        'footer' => 'شكراً لمشاركتك في أنشطة TeamHUB.',
    ],

    // تُرسل للطالب بعد نجاح التسجيل في الفعالية
    'rsvp_confirmation' => [
        'subject' => 'تم تسجيلك في :title',
        'greeting' => 'مرحباً :name،',
        'body' => 'تم تأكيد تسجيلك في فعالية ":title". تبدأ بتاريخ :date في :location.',
        'action' => 'عرض الفعالية',
        'footer' => 'بانتظارك! يمكنك إلغاء تسجيلك في أي وقت من صفحة الفعالية.',
    ],

    // تُرسل للمسجّلين عند إلغاء الفعالية
    'cancelled_notification' => [
        'subject' => 'أُلغيت: :title',
        'greeting' => 'مرحباً :name،',
        'body' => 'يؤسفنا إبلاغك بأن فعالية ":title" (المقررة بتاريخ :date) قد أُلغيت.',
        'action' => 'تصفّح فعاليات أخرى',
        'footer' => 'نعتذر عن أي إزعاج.',
    ],

    // تُرسل للمسجّلين عند تغيّر موعد الفعالية أو مكانها
    'updated_notification' => [
        'subject' => 'تحديث تفاصيل :title',
        'greeting' => 'مرحباً :name،',
        'body' => 'تغيّرت تفاصيل فعالية ":title". أصبحت الآن بتاريخ :date في :location.',
        'action' => 'عرض الفعالية',
        'footer' => 'يرجى مراجعة التفاصيل الجديدة حتى لا تفوتك.',
    ],

    // Managed events (supervisor dashboard)
    'managed' => [
        'registrations' => ':count مسجّل',
        'registrations_of' => ':count من :total مسجّل',
        'no_capacity' => 'غير محدود',
    ],
];
