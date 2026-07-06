<?php

return [
    'title' => 'طلب الانضمام',
    'submitted' => 'تم إرسال طلب الانضمام بنجاح. سيتم مراجعته من قبل قائد مساحة العمل.',
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
    'join_workspace' => 'الانضمام لمساحة العمل',
    'placeholder' => [
        'full_name' => 'احمد محمد احمد محمد',
        'university_email' => 'name@example.com',
        'phone' => '05000000000',
        'level' => 'المستوى العاشر',
        'major' => 'هندسة البرمجيات ..',
        'skills' => 'التصميم الجرافيكي ...',
        'weekly_hours' => '4',
        'tools' => 'مثل: Photoshop, Canva, Office, Notion',
        'motivation' => 'أخبرنا لماذا تناسبك مساحة العمل هذه',
        'contribution' => 'ما القيمة التي ستضيفها لمساحة العمل؟',
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
        'workspace' => [
            'inactive' => 'مساحة العمل هذه غير متاحة للانضمام حالياً.',
            'already_member' => 'أنت عضو بالفعل في مساحة العمل هذه.',
            'pending_application' => 'لديك طلب انضمام قيد المراجعة لمساحة العمل هذه.',
        ],
    ],

    'notification' => [
        'approved' => [
            'subject' => 'تم قبول عضويتك في :workspace',
            'greeting' => 'مبارك يا :name!',
            'body' => 'تمت الموافقة على طلب انضمامك إلى ":workspace". أنت الآن عضو ويمكنك المشاركة في أنشطتها.',
            'action' => 'زيارة مساحة العمل',
            'footer' => 'أهلاً بك في مجتمع TeamHUB.',
        ],
        'rejected' => [
            'subject' => 'تحديث بخصوص طلب عضويتك في :workspace',
            'greeting' => 'مرحباً :name،',
            'body' => 'نعتذر، لم تتم الموافقة على طلب انضمامك إلى ":workspace" هذه المرة. يمكنك استكشاف مساحات عمل أخرى أو التقديم مرة أخرى لاحقاً.',
            'action' => 'تصفّح مساحات العمل',
            'footer' => 'شكراً لاهتمامك بمنصة TeamHUB.',
        ],
        'received' => [
            'subject' => 'طلب عضوية جديد لمساحة عمل :workspace',
            'greeting' => 'مرحباً :name،',
            'body' => 'تقدّم :applicant بطلب للانضمام إلى ":workspace". يرجى مراجعة الطلب عندما يتاح لك ذلك.',
            'action' => 'مراجعة الطلب',
            'footer' => 'تصلك هذه الرسالة لأنك تدير أعضاء مساحة العمل :workspace.',
        ],
    ],
];
