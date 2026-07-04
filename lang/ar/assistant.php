<?php

return [
    'title' => 'مساعد TeamHUB',
    'subtitle' => 'اسأل عن مساحات العمل والمشاريع والمهام والعوائق والخطوات التالية.',
    'open' => 'افتح مساعد TeamHUB',
    'close' => 'إغلاق المساعد',
    'placeholder' => 'اكتب رسالتك…',
    'send' => 'إرسال',
    'greeting' => 'مرحبًا! كيف أساعدك اليوم؟',
    'greeting_hint' => 'يمكنني تلخيص مهامك، وشرح ما هو متعطل في مشروعك، وتجهيز تحديثات المهام بانتظار تأكيدك.',
    'thinking' => 'يكتب…',
    'reasoning' => 'مسار التفكير',
    'error' => 'تعذّر الحصول على رد. حاول مرة أخرى.',
    'you' => 'أنت',
    'assistant' => 'المساعد',
    'new_chat' => 'محادثة جديدة',
    'suggestions_title' => 'جرّب أن تسأل',
    'confirm' => 'تأكيد',
    'cancel' => 'إلغاء',
    'confirmation_cancelled' => 'تم إلغاء الإجراء المقترح.',
    'confirmation_cancelled_message' => 'تم إلغاء الإجراء المقترح.',
    'confirmation_success_prefix' => 'تم تنفيذ الإجراء بنجاح',
    'confirmation_failure_prefix' => 'فشل تنفيذ الإجراء',
    'confirmation_connection_error' => 'فشل تنفيذ الإجراء بسبب خطأ في الاتصال.',

    // عناوين نشاط الأدوات التي تظهر أثناء عمل المساعد. المفاتيح هي أسماء أصناف
    // الأدوات؛ ويغطّي `default` أي أداة بلا سطر خاص بها.
    'activity' => [
        'default' => 'جارٍ العمل…',
        'GetAppRoutes' => 'جارٍ العثور على صفحة TeamHUB المناسبة…',
        'ListMyTasks' => 'جارٍ مراجعة مهامك…',
        'FindTasks' => 'جارٍ البحث في مهام المشاريع…',
        'GetProjectSummary' => 'جارٍ تلخيص المشروع…',
        'CreateTask' => 'جارٍ تجهيز المهمة الجديدة…',
        'AssignTask' => 'جارٍ تجهيز إسناد المهمة…',
        'UpdateTaskStatus' => 'جارٍ تجهيز تحديث حالة المهمة…',
        'UpdateTaskDetails' => 'جارٍ تجهيز تعديل تفاصيل المهمة…',
    ],

    // مقترحات بدء المحادثة في الحالة الفارغة. تُرسل كما هي عند النقر، لذا صيغت
    // بصيغة المتحدّث.
    'suggestions' => [
        'login_help' => 'كيف أسجّل الدخول إلى TeamHUB؟',
        'find_my_tasks_page' => 'أين أجد صفحة مهامي بعد تسجيل الدخول؟',
        'teamhub_pages' => 'أرني الصفحات الأساسية في TeamHUB.',
        'teamhub_capabilities' => 'ما الذي يستطيع مساعد TeamHUB مساعدتي فيه؟',
        'my_overdue_tasks' => 'ما المهام المتأخرة؟',
        'my_tasks_today' => 'ما المهام المستحقة اليوم؟',
        'my_open_tasks' => 'لخّص مهامي المفتوحة.',
        'my_upcoming_tasks' => 'ما المهام القادمة بعد ذلك؟',
        'project_blockers' => 'ما الذي يعيق مشروع :project؟',
        'project_summary' => 'أعطني ملخصًا عن مشروع :project.',
        'create_task_due_friday' => 'أنشئ مهمة تستحق يوم الجمعة.',
        'assign_task_example' => 'أسند هذه المهمة إلى أحمد.',
        'move_task_to_review' => 'انقل هذه المهمة إلى المراجعة.',
    ],
];
