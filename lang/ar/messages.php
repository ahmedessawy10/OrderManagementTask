<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Messages Language Lines
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'sending_failed' => 'فشل في إرسال الرسالة القصيرة (SMS).',
    ],

    'otp' => [
        'message' => 'كود التحقق الخاص بك هو: :code :app_key',
    ],

    'validation_error' => 'البيانات المدخلة غير صحيحة.',
    'unknown_error' => 'حدث خطأ غير معروف.',

    'auth' => [
        'register_success' => 'تم التسجيل بنجاح. تم إرسال كود التحقق إلى هاتفك.',
        'otp_verified' => 'تم تأكيد كود التحقق بنجاح',
        'otp_sent' => 'تم إرسال كود التحقق بنجاح',
        'login_success' => 'تم تسجيل الدخول بنجاح',
        'password_reset_success' => 'تمت إعادة تعيين كلمة المرور بنجاح',
        'logout_success' => 'تم تسجيل الخروج بنجاح',
        'account_deleted' => 'تم حذف الحساب بنجاح',
        'language_updated' => 'تم تحديث اللغة بنجاح',
        'profile_retrieved' => 'تم استرجاع الملف الشخصي بنجاح',
        'profile_image_updated' => 'تم تحديث صورة الملف الشخصي بنجاح',
        'password_changed' => 'تم تغيير كلمة المرور بنجاح',
        'token_refreshed' => 'تم تحديث الرمز بنجاح',
        'account_blocked' => 'تم حظر حسابك. :reason',
        'invalid_otp' => 'كود التحقق غير صحيح أو منتهي الصلاحية.',
        'phone_already_taken' => 'رقم الهاتف مستخدم بالفعل.',
        'invalid_otp_type' => 'نوع كود التحقق غير صحيح.',
        'phone_not_found' => 'لا يوجد حساب مرتبط بهذا الرقم.',
        'phone_already_registered' => 'رقم الهاتف مفعل بالفعل.',
        'invalid_credentials' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
        'phone_not_verified' => 'رقم الهاتف غير مفعل. تم إرسال كود التحقق.',
        'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة.',
        'same_password' => 'يجب أن تختلف كلمة المرور الجديدة عن الحالية.',
        'token_expired_or_invalid' => 'الرمز غير صالح أو منتهي الصلاحية.',
        'invalid_token_type' => 'نوع الرمز غير صحيح.',
        'user_not_found' => 'المستخدم غير موجود.',
        'unauthenticated' => 'غير مصرح بالدخول.',
        'invalid_google_token' => 'رمز Google غير صالح.',
        'google_token_expired' => 'انتهت صلاحية رمز Google.',
        'google_keys_fetch_failed' => 'تعذر جلب مفاتيح Google العامة.',
    ],

    'notification' => [
        'device_token_stored' => 'تم حفظ رمز الجهاز بنجاح',
        'device_token_removed' => 'تم حذف رمز الجهاز بنجاح',
        'retrieved' => 'تم استرجاع الإشعارات بنجاح',
        'unread_count_retrieved' => 'تم استرجاع عدد الإشعارات غير المقروءة بنجاح',
        'not_found' => 'الإشعار غير موجود',
        'marked_read' => 'تم تحديد الإشعار كمقروء',
        'all_marked_read' => 'تم تحديد كل الإشعارات كمقروءة',
        'deleted' => 'تم حذف الإشعار بنجاح',
        'status_updated' => 'تم تحديث إعدادات الإشعارات بنجاح',
    ],

    'fcm' => [
        'not_configured' => 'الإشعارات الفورية غير مهيأة.',
        'credentials_file_not_found' => 'ملف بيانات اعتماد FCM غير موجود.',
        'invalid_json_credentials' => 'ملف بيانات اعتماد FCM ليس JSON صالحا.',
        'missing_required_credentials' => 'بيانات اعتماد FCM ينقصها حقول مطلوبة.',
        'oauth_token_request_failed' => 'فشل طلب رمز الوصول من FCM: :error',
        'no_access_token_in_response' => 'رد FCM لا يحتوي على رمز وصول.',
        'failed_to_sign_jwt' => 'فشل توقيع JWT الخاص بـ FCM.',
    ],

    'product' => [
        'retrieved' => 'تم استرجاع المنتجات بنجاح',
    ],

    'order' => [
        'retrieved' => 'تم استرجاع الطلبات بنجاح',
        'created' => 'تم إنشاء الطلب بنجاح',
        'updated' => 'تم تحديث الطلب بنجاح',
        'deleted' => 'تم حذف الطلب بنجاح',
        'shown' => 'تم استرجاع الطلب بنجاح',
        'not_editable' => 'لا يمكن تعديل عناصر الطلب إلا وهو قيد الانتظار.',
        'has_payments' => 'لا يمكن حذف هذا الطلب لوجود مدفوعات مرتبطة به.',
        'invalid_transition' => 'لا يمكن تغيير حالة الطلب من :from إلى :to.',
        'cannot_cancel' => 'لا يمكن إلغاء طلب مدفوع أو عليه عملية دفع قيد التنفيذ.',
    ],

    'payment' => [
        'retrieved' => 'تم استرجاع المدفوعات بنجاح',
        'shown' => 'تم استرجاع عملية الدفع بنجاح',
        'processed' => 'تمت عملية الدفع بنجاح',
        'processing' => 'عملية الدفع قيد المعالجة، وسيتم تأكيد حالتها النهائية قريبًا.',
        'failed' => 'فشلت عملية الدفع',
        'order_not_payable' => 'لا يمكن الدفع إلا للطلبات قيد الانتظار.',
        'order_already_paid' => 'تم دفع هذا الطلب مسبقًا.',
        'already_active' => 'يوجد عملية دفع قيد التنفيذ لهذا الطلب بالفعل.',
        'gateway_timeout' => 'لم تستجب بوابة الدفع في الوقت المحدد.',
    ],

    'webhook' => [
        'received' => 'تم استلام الإشعار',
        'gateway_not_found' => 'بوابة الدفع غير موجودة.',
        'payment_not_found' => 'لا توجد عملية دفع مطابقة لهذا المرجع بعد. يرجى إعادة المحاولة لاحقا.',
        'invalid_status' => 'حالة الويبهوك يجب أن تكون successful أو failed.',
        'amount_mismatch' => 'المبلغ أو العملة في الإشعار لا يطابق عملية الدفع.',
    ],

    'errors' => [
        'validation' => 'البيانات المدخلة غير صالحة.',
        'bad_request' => 'طلب غير صالح.',
        'unauthenticated' => 'غير مصرح. يرجى تسجيل الدخول والمحاولة مرة أخرى.',
        'forbidden' => 'غير مسموح لك بتنفيذ هذا الإجراء.',
        'not_found' => 'المورد المطلوب غير موجود.',
        'method_not_allowed' => 'طريقة الطلب غير مسموحة لهذا المسار.',
        'too_many_requests' => 'طلبات كثيرة جدًا. يرجى المحاولة لاحقًا.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقًا.',
        'http' => 'تعذر إتمام الطلب.',
    ],

    'enum' => [
        'userstatus' => [
            'active' => 'نشط',
            'blocked' => 'محظور',
            'incomplete' => 'غير مكتمل',
        ],
        'devicetype' => [
            'android' => 'أندرويد',
            'ios' => 'iOS',
            'web' => 'الويب',
        ],
        'otptype' => [
            'registration' => 'التسجيل',
            'login' => 'تسجيل الدخول',
            'password_reset' => 'إعادة تعيين كلمة المرور',
            'change_phone' => 'تغيير رقم الهاتف',
            'complete_registration' => 'إكمال التسجيل',
        ],
        'smsprovider' => [
            'sms' => 'رسالة نصية',
            'whatsapp' => 'واتساب',
        ],
        'notificationtype' => [
            'order_placed' => 'تم إنشاء الطلب',
            'order_confirmed' => 'تم تأكيد الطلب',
            'order_preparing' => 'جاري تجهيز الطلب',
            'order_ready' => 'الطلب جاهز',
            'order_out_for_delivery' => 'الطلب في الطريق',
            'order_delivered' => 'تم توصيل الطلب',
            'order_cancelled' => 'تم إلغاء الطلب',
            'order_edited' => 'تم تعديل الطلب',
            'loyalty_points_earned' => 'تم كسب نقاط ولاء',
            'loyalty_points_expiring' => 'نقاط الولاء على وشك الانتهاء',
            'review_request' => 'طلب تقييم',
            'driver_assigned' => 'تم تعيين السائق',
            'address_update_request' => 'طلب تحديث العنوان',
            'branch_available' => 'الفرع متاح',
            'general' => 'عام',
        ],
        'orderstatus' => [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'cancelled' => 'ملغي',
        ],
        'paymentstatus' => [
            'pending' => 'قيد الانتظار',
            'successful' => 'ناجحة',
            'failed' => 'فاشلة',
        ],
    ],

];
