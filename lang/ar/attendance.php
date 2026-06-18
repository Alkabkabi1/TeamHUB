<?php

return [
    // Scanner page (club Attendance Scanner)
    'scan' => [
        'title' => 'ماسح الحضور',
        'subtitle' => 'امسح رمز QR الخاص بالطالب لتسجيل حضوره لهذا اليوم.',
        'back' => 'العودة إلى لوحة التحكم',
        'start_camera' => 'تشغيل الكاميرا',
        'stop_camera' => 'إيقاف الكاميرا',
        'camera_hint' => 'وجّه الكاميرا نحو رمز QR الخاص بحضور الطالب.',
        'camera_error' => 'تعذّر الوصول إلى الكاميرا. تحقّق من أذونات المتصفح وحاول مرة أخرى.',
        'today' => 'اليوم',
        'checked_in_today_count' => 'تم تسجيل حضور :count اليوم',
        'checked_in_today' => 'حضر اليوم',
        'not_checked_in' => 'لم يحضر اليوم',
        'days_attended' => ':count أيام',
        'roster_title' => 'الطلاب المسجّلون',
        'roster_empty' => 'لم يسجّل أي طالب في هذا النشاط بعد.',
        'result' => [
            'checked_in' => 'تم تسجيل حضور :name.',
            'already_today' => 'تم تسجيل حضور :name مسبقًا اليوم.',
            'walk_in' => 'تم تسجيل حضور :name كحاضر دون تسجيل مسبق.',
            'invalid' => 'رمز QR غير معروف.',
        ],
    ],

    // Attendance action on the club management dashboard's event cards
    'manage' => [
        'scan_button' => 'مسح الحضور',
    ],
];
