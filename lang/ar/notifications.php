<?php

return [
    'title' => 'الإشعارات',
    'subtitle' => 'تابع الإسنادات والمراجعات وتحديثات المهام من مكان واحد.',
    'unread' => 'غير المقروءة',
    'read' => 'الأقدم',
    'empty' => 'لا توجد إشعارات بعد.',
    'mark_read' => 'تحديد كمقروء',
    'mark_all_read' => 'تحديد الكل كمقروء',
    'open' => 'فتح',
    'task_assigned' => [
        'title' => 'تم إسناد مهمة جديدة',
        'body' => 'أسند :actor المهمة \":task\" في :project.',
        'action' => 'فتح المهمة',
        'mail_subject' => 'تم إسناد مهمة جديدة: :task',
    ],
    'task_submitted_for_review' => [
        'title' => 'تم إرسال المهمة للمراجعة',
        'body' => 'أرسل :actor مخرجات \":task\" للمراجعة في :project.',
        'action' => 'مراجعة المهمة',
        'mail_subject' => ':task جاهزة للمراجعة',
    ],
    'task_deliverable_approved' => [
        'title' => 'تم اعتماد المخرجات',
        'body' => 'اعتمد :actor مخرجات \":task\".',
        'action' => 'فتح المهمة',
        'mail_subject' => 'تم اعتماد :task',
    ],
    'task_changes_requested' => [
        'title' => 'تم طلب تعديلات',
        'body' => 'طلب :actor تعديلات على \":task\".',
        'action' => 'فتح المهمة',
        'mail_subject' => 'تم طلب تعديلات على :task',
    ],
    'admin_message' => [
        'title' => 'رسالة من المسؤول',
        'body' => 'يقول :sender: :message',
        'action' => 'فتح لوحة التحكم',
        'mail_subject' => 'رسالة من :sender',
    ],
];
