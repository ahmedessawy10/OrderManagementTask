<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول حقل :attribute.',
    'accepted_if' => 'يجب قبول حقل :attribute عندما يكون :other هو :value.',
    'active_url' => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
    'after' => 'حقل :attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي حقل :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام فقط.',
    'any_of' => 'حقل :attribute غير صالح.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات للمفاتيح التالية: :values.',
    'ascii' => 'يجب أن يحتوي حقل :attribute على أحرف ورموز أبجدية رقمية أحادية البايت فقط.',
    'base64' => 'يجب أن يكون حقل :attribute نص Base64 صالحاً.',
    'before' => 'حقل :attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي حقل :attribute على عدد عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم حقل :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول نص حقل :attribute بين :min و :max حرفاً.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute إما صحيح (true) أو خطأ (false).',
    'can' => 'حقل :attribute يحتوي على قيمة غير مصرح بها.',
    'confirmed' => 'حقل تأكيد :attribute غير متطابق.',
    'contains' => 'حقل :attribute يفتقد إلى قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute يجب أن يكون تاريخاً صالحاً.',
    'date_equals' => 'يجب أن يكون حقل :attribute تاريخاً مساوياً لـ :date.',
    'date_format' => 'يجب أن يتطابق حقل :attribute مع الصيغة :format.',
    'decimal' => 'يجب أن يحتوي حقل :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض حقل :attribute.',
    'declined_if' => 'يجب رفض حقل :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون حقل :attribute ومجال :other مختلفين.',
    'digits' => 'يجب أن يحتوي حقل :attribute على :digits أرقام.',
    'digits_between' => 'يجب أن يحتوي حقل :attribute على عدد أرقام بين :min و :max.',
    'dimensions' => 'حقل :attribute يحتوي على أبعاد صورة غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_contain' => 'يجب ألا يحتوي حقل :attribute على أي من: :values.',
    'doesnt_end_with' => 'يجب ألا ينتهي حقل :attribute بأي من: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ حقل :attribute بأي من: :values.',
    'email' => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صالحاً.',
    'encoding' => 'يجب أن يكون حقل :attribute مشفراً بترميز :encoding.',
    'ends_with' => 'يجب أن ينتهي حقل :attribute بأحد القيم التالية: :values.',
    'enum' => 'قيمة :attribute المحددة غير صالحة.',
    'exists' => 'قيمة :attribute المحددة غير صالحة.',
    'extensions' => 'يجب أن يكون لحقل :attribute أحد الامتدادات التالية: :values.',
    'file' => 'يجب أن يكون حقل :attribute ملفاً.',
    'filled' => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول نص حقل :attribute أكثر من :value حرفاً.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :value عنصر أو أكثر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون طول نص حقل :attribute أكبر من أو يساوي :value حرفاً.',
    ],
    'hex_color' => 'يجب أن يكون حقل :attribute لوناً ست عشرياً صالحاً.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'قيمة :attribute المحددة غير صالحة.',
    'in_array' => 'يجب أن يكون حقل :attribute موجوداً في :other.',
    'in_array_keys' => 'يجب أن يحتوي حقل :attribute على مفتاح واحد على الأقل من: :values.',
    'integer' => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'ip' => 'يجب أن يكون حقل :attribute عنوان IP صالحاً.',
    'ipv4' => 'يجب أن يكون حقل :attribute عنوان IPv4 صالحاً.',
    'ipv6' => 'يجب أن يكون حقل :attribute عنوان IPv6 صالحاً.',
    'json' => 'يجب أن يكون حقل :attribute نص JSON صالحاً.',
    'list' => 'يجب أن يكون حقل :attribute قائمة.',
    'lowercase' => 'يجب أن يكون حقل :attribute مكتوباً بأحرف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أقل من :value عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أقل من :value.',
        'string' => 'يجب أن يكون طول نص حقل :attribute أقل من :value حرفاً.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب ألا يزيد حجم ملف حقل :attribute عن :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أقل من أو تساوي :value.',
        'string' => 'يجب ألا يزيد طول نص حقل :attribute عن :value حرفاً.',
    ],
    'mac_address' => 'يجب أن يكون حقل :attribute عنوان MAC صالحاً.',
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يتجاوز حجم ملف حقل :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة حقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز طول نص حقل :attribute :max حرفاً.',
    ],
    'max_digits' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max أرقام.',
    'mimes' => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'mimetypes' => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على ما لا يقل عن :min عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute على الأقل :min.',
        'string' => 'يجب أن يكون طول نص حقل :attribute على الأقل :min حروف.',
    ],
    'min_digits' => 'يجب أن يحتوي حقل :attribute على ما لا يقل عن :min أرقام.',
    'missing' => 'يجب ألا يتوفر حقل :attribute.',
    'missing_if' => 'يجب ألا يتوفر حقل :attribute عندما يكون :other هو :value.',
    'missing_unless' => 'يجب ألا يتوفر حقل :attribute ما لم يكن :other هو :value.',
    'missing_with' => 'يجب ألا يتوفر حقل :attribute عند وجود :values.',
    'missing_with_all' => 'يجب ألا يتوفر حقل :attribute عند وجود جميع :values.',
    'multiple_of' => 'يجب أن يكون حقل :attribute من مضاعفات :value.',
    'not_in' => 'قيمة :attribute المحددة غير صالحة.',
    'not_regex' => 'صيغة حقل :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون حقل :attribute رقماً.',
    'password' => [
        'letters' => 'يجب أن يحتوي حقل :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي حقل :attribute على حرف كبير وآخر صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي حقل :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي حقل :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'قيمة :attribute المعطاة ظهرت في تسريب بيانات. يرجى اختيار :attribute آخر.',
    ],
    'present' => 'يجب تقديم حقل :attribute.',
    'present_if' => 'يجب تقديم حقل :attribute عندما يكون :other هو :value.',
    'present_unless' => 'يجب تقديم حقل :attribute ما لم يكن :other هو :value.',
    'present_with' => 'يجب تقديم حقل :attribute عند وجود :values.',
    'present_with_all' => 'يجب تقديم حقل :attribute عند وجود جميع :values.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_if_accepted' => 'حقل :attribute محظور عندما يتم قبول :other.',
    'prohibited_if_declined' => 'حقل :attribute محظور عندما يتم رفض :other.',
    'prohibited_unless' => 'حقل :attribute محظور ما لم يكن :other ضمن :values.',
    'prohibits' => 'حقل :attribute يمنع تواجد حقل :other.',
    'regex' => 'صيغة حقل :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات للمفاتيح: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عندما يتم قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عندما يتم رفض :other.',
    'required_unless' => 'حقل :attribute مطلوب ما لم يكن :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عند توفر :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند توفر جميع :values.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يتوفر :values.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا يتوفر أي من :values.',
    'same' => 'يجب أن يتطابق حقل :attribute مع حقل :other.',
    'size' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :size عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية لـ :size.',
        'string' => 'يجب أن يحتوي نص حقل :attribute على :size حرفاً.',
    ],
    'starts_with' => 'يجب أن يبدأ حقل :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون حقل :attribute نصاً.',
    'timezone' => 'يجب أن يكون حقل :attribute نطاقاً زمنياً صالحاً.',
    'unique' => 'قيمة حقل :attribute مستخدمة من قبل.',
    'uploaded' => 'فشل في تحميل حقل :attribute.',
    'uppercase' => 'يجب أن يكون حقل :attribute مكتوباً بأحرف كبيرة.',
    'url' => 'يجب أن يكون حقل :attribute رابطاً صالحاً.',
    'ulid' => 'يجب أن يكون حقل :attribute معرف ULID صالحاً.',
    'uuid' => 'يجب أن يكون حقل :attribute معرف UUID صالحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'رسالة مخصصة',
        ],

        // Auth
        'name' => [
            'required' => 'الاسم مطلوب.',
        ],
        'email' => [
            'email' => 'يجب أن يكون البريد الإلكتروني صالحا.',
            'unique' => 'البريد الإلكتروني مستخدم بالفعل.',
        ],
        'phone' => [
            'required' => 'رقم الهاتف مطلوب.',
            'regex' => 'يجب أن يكون رقم الهاتف كويتيا صالحا (00965 أو +965 متبوعا بـ 8 أرقام).',
            'exists' => 'لا يوجد حساب مرتبط بهذا الرقم.',
            'unique' => 'رقم الهاتف مسجل بالفعل.',
        ],
        'password' => [
            'required' => 'كلمة المرور مطلوبة.',
            'min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
            'confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ],
        'current_password' => [
            'required' => 'كلمة المرور الحالية مطلوبة.',
        ],
        'code' => [
            'required' => 'كود التحقق مطلوب.',
            'size' => 'يجب أن يتكون كود التحقق من 4 أرقام.',
        ],
        'type' => [
            'required' => 'نوع التحقق مطلوب.',
            'enum' => 'نوع التحقق المحدد غير صحيح.',
        ],
        'user_id' => [
            'required' => 'المستخدم مطلوب.',
            'integer' => 'يجب أن يكون معرف المستخدم رقما صحيحا.',
            'exists' => 'المستخدم المحدد غير موجود.',
        ],
        'language' => [
            'required' => 'اللغة مطلوبة.',
            'in' => 'يجب أن تكون اللغة en أو ar.',
        ],
        'profile_image' => [
            'required' => 'صورة الملف الشخصي مطلوبة.',
            'image' => 'يجب أن تكون صورة الملف الشخصي صورة.',
            'mimes' => 'يجب أن تكون صورة الملف الشخصي من نوع jpeg أو jpg أو png أو webp.',
            'max' => 'يجب ألا يزيد حجم صورة الملف الشخصي عن 2 ميجابايت.',
        ],

        // Orders
        'items' => [
            'required' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل.',
            'array' => 'العناصر يجب أن تكون مصفوفة.',
            'min' => 'يجب أن يحتوي الطلب على :min عنصر على الأقل.',
            'max' => 'يجب ألا يحتوي الطلب على أكثر من :max عنصر.',
            'total_max' => 'إجمالي الطلب يجب ألا يتجاوز :max.',
            'item' => [
                'required' => 'كل عنصر مطلوب.',
                'array' => 'كل عنصر يجب أن يحتوي على رقم المنتج والكمية فقط.',
            ],
            'product_id' => [
                'required' => 'المنتج مطلوب لكل عنصر.',
                'integer' => 'رقم المنتج يجب أن يكون عددًا صحيحًا.',
                'distinct' => 'لا يمكن إضافة نفس المنتج مرتين، قم بزيادة الكمية بدلًا من ذلك.',
                'exists' => 'المنتج المختار غير موجود.',
            ],
            'quantity' => [
                'required' => 'الكمية مطلوبة لكل عنصر.',
                'integer' => 'الكمية يجب أن تكون عددًا صحيحًا.',
                'min' => 'الكمية يجب أن تكون :min على الأقل.',
                'max' => 'الكمية يجب ألا تتجاوز :max.',
            ],
        ],
        'status' => [
            'required' => 'الحالة مطلوبة.',
            'in' => 'يمكنك إلغاء الطلب فقط، ويتم تأكيده عند نجاح الدفع.',
            'enum' => 'الحالة المختارة غير صالحة.',
        ],

        // Payments
        'payment_method' => [
            'required' => 'طريقة الدفع مطلوبة.',
            'string' => 'طريقة الدفع يجب أن تكون نصًا.',
            'in' => 'طريقة الدفع المختارة غير مدعومة.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'email' => 'البريد الإلكتروني',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'city' => 'المدينة',
        'country' => 'الدولة',
        'address' => 'العنوان',
        'phone' => 'الهاتف',
        'mobile' => 'الجوال',
        'age' => 'العمر',
        'sex' => 'الجنس',
        'gender' => 'النوع',
        'day' => 'اليوم',
        'month' => 'الشهر',
        'year' => 'السنة',
        'hour' => 'الساعة',
        'minute' => 'الدقيقة',
        'second' => 'الثانية',
        'title' => 'العنوان',
        'content' => 'المحتوى',
        'description' => 'الوصف',
        'excerpt' => 'المقتطف',
        'date' => 'التاريخ',
        'time' => 'الوقت',
        'available' => 'متاح',
        'size' => 'الحجم',
        'price' => 'السعر',
        'desc' => 'الوصف',
        'message' => 'الرسالة',
        'role' => 'الدور',
        'image' => 'الصورة',
    ],

];
