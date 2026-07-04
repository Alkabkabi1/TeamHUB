<?php

return [
    'title' => 'طلب الانضمام',
    'submitted' => 'تم إرسال طلب الانضمام بنجاح. سيتم مراجعته من قبل مشرف النادي.',
    'full_name' => 'الاسم الكامل',
    'university_email' => 'البريد الإلكتروني',
    'phone' => 'رقم الجوال',
    'level' => 'المستوى الدراسي',
    'major' => 'التخصص',
    'skills' => 'المهارات',
    'weekly_hours' => 'الساعات الأسبوعية المتاحة',
    'tools' => 'الأدوات والبرامج',
    'motivation' => 'سبب الانضمام',
    'contribution' => 'القيمة المضافة',
    'submit' => 'إرسال الطلب',
    'join_club' => 'الانضمام للنادي',
    'placeholder' => [
        'full_name' => 'احمد محمد احمد محمد',
        'university_email' => 'name@example.com',
        'phone' => '05000000000',
        'level' => 'المستوى العاشر',
        'major' => 'هندسة البرمجيات ..',
        'skills' => 'التصميم الجرافيكي ...',
        'weekly_hours' => '4',
        'tools' => 'مثل: Photoshop, Canva, Office, Notion',
        'motivation' => 'أخبرنا لماذا يناسبك هذا النادي',
        'contribution' => 'ما القيمة التي ستضيفها للنادي؟',
    ],
    'validation' => [
        'full_name' => [
            'required' => 'اسم الطالب مطلوب',
        ],
        'university_email' => [
            'required' => 'البريد الإلكتروني مطلوب',
            'email' => 'البريد الإلكتروني غير صالح',
            'mismatch' => 'يجب أن يطابق البريد الإلكتروني بريد حسابك المسجل.',
        ],
        'phone' => [
            'required' => 'رقم التواصل مطلوب',
        ],
        'level' => [
            'required' => 'المستوى الدراسي مطلوب',
        ],
        'major' => [
            'required' => 'التخصص مطلوب',
        ],
        'skills' => [
            'required' => 'الرجاء توضيح المهارات',
        ],
        'weekly_hours' => [
            'required' => 'حدد عدد الساعات',
        ],
        'tools' => [
            'required' => 'الرجاء ذكر البرامج',
        ],
        'motivation' => [
            'required' => 'الرجاء كتابة سبب الانضمام',
        ],
        'contribution' => [
            'required' => 'الرجاء كتابة القيمة المضافة',
        ],
        'club' => [
            'inactive' => 'هذا النادي غير متاح للانضمام حالياً.',
            'already_member' => 'أنت عضو بالفعل في هذا النادي.',
            'pending_application' => 'لديك طلب انضمام قيد المراجعة لهذا النادي.',
        ],
    ],

    // Membership decision notifications (sent to the applicant)
    'notification' => [
        'approved' => [
            'subject' => 'تم قبول عضويتك في :club',
            'greeting' => 'مبارك يا :name!',
            'body' => 'تمت الموافقة على طلب انضمامك إلى ":club". أنت الآن عضو ويمكنك المشاركة في فعاليات النادي وأنشطته.',
            'action' => 'زيارة النادي',
            'footer' => 'أهلاً بك في مجتمع TeamHUB.',
        ],
        'rejected' => [
            'subject' => 'تحديث بخصوص طلب عضويتك في :club',
            'greeting' => 'مرحباً :name،',
            'body' => 'نعتذر، لم تتم الموافقة على طلب انضمامك إلى ":club" هذه المرة. يمكنك استكشاف أندية أخرى أو التقديم مرة أخرى لاحقاً.',
            'action' => 'تصفّح الأندية',
            'footer' => 'شكراً لاهتمامك بمنصة TeamHUB.',
        ],
        'received' => [
            'subject' => 'طلب عضوية جديد لنادي :club',
            'greeting' => 'مرحباً :name،',
            'body' => 'تقدّم :applicant بطلب للانضمام إلى ":club". يرجى مراجعة الطلب عندما يتاح لك ذلك.',
            'action' => 'مراجعة الطلب',
            'footer' => 'تصلك هذه الرسالة لأنك تدير أعضاء نادي :club.',
        ],
    ],
];
