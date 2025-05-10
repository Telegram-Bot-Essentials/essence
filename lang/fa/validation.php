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

    'accepted' => 'فیلد :attribute باید پذیرفته شود. ✅',
    'accepted_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است، پذیرفته شود. ✅',
    'active_url' => 'فیلد :attribute باید یک URL معتبر باشد. 🌐',
    'after' => 'فیلد :attribute باید یک تاریخ پس از :date باشد. 📅',
    'after_or_equal' => 'فیلد :attribute باید یک تاریخ پس از یا برابر با :date باشد. 📅',
    'alpha' => 'فیلد :attribute باید فقط شامل حروف باشد. 🔤',
    'alpha_dash' => 'فیلد :attribute باید فقط شامل حروف، اعداد، خط تیره و زیرخط باشد. 🔤-',
    'alpha_num' => 'فیلد :attribute باید فقط شامل حروف و اعداد باشد. 🔡123',
    'array' => 'فیلد :attribute باید یک آرایه باشد. 🧳',
    'ascii' => 'فیلد :attribute باید فقط شامل کاراکترهای آلفانومریک و نمادهای تک‌بایت باشد. 🔠',
    'before' => 'فیلد :attribute باید یک تاریخ قبل از :date باشد. 📅',
    'before_or_equal' => 'فیلد :attribute باید یک تاریخ قبل از یا برابر با :date باشد. 📅',
    'between' => [
        'array' => 'فیلد :attribute باید بین :min و :max آیتم داشته باشد. 📊',
        'file' => 'فیلد :attribute باید بین :min و :max کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید بین :min و :max باشد. 🔢',
        'string' => 'فیلد :attribute باید بین :min و :max کاراکتر باشد. 🔤',
    ],
    'boolean' => 'فیلد :attribute باید صحیح یا غلط باشد. ✔️❌',
    'can' => 'فیلد :attribute دارای یک مقدار غیرمجاز است. 🚫',
    'confirmed' => 'تأیید فیلد :attribute با هم مطابقت ندارد. ❌',
    'contains' => 'فیلد :attribute مقدار ضروری را ندارد. ⚠️',
    'current_password' => 'رمز عبور اشتباه است. 🔒',
    'date' => 'فیلد :attribute باید یک تاریخ معتبر باشد. 📅',
    'date_equals' => 'فیلد :attribute باید یک تاریخ برابر با :date باشد. 📅',
    'date_format' => 'فیلد :attribute باید با فرمت :format مطابقت داشته باشد. 📅🗓️',
    'decimal' => 'فیلد :attribute باید :decimal رقم اعشاری داشته باشد. .🔢',
    'declined' => 'فیلد :attribute باید رد شود. ❌',
    'declined_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است، رد شود. ❌',
    'different' => 'فیلد :attribute و :other باید متفاوت باشند. 🔄',
    'digits' => 'فیلد :attribute باید :digits رقم باشد. 🔢',
    'digits_between' => 'فیلد :attribute باید بین :min و :max رقم باشد. 🔢',
    'dimensions' => 'ابعاد تصویر فیلد :attribute نامعتبر است. 🖼️',
    'distinct' => 'فیلد :attribute دارای یک مقدار تکراری است. 🔁',
    'doesnt_end_with' => 'فیلد :attribute نباید با یکی از مقادیر زیر تمام شود: :values. 🚫',
    'doesnt_start_with' => 'فیلد :attribute نباید با یکی از مقادیر زیر شروع شود: :values. 🚫',
    'email' => 'فیلد :attribute باید یک آدرس ایمیل معتبر باشد. 📧',
    'ends_with' => 'فیلد :attribute باید با یکی از مقادیر زیر تمام شود: :values. ✅',
    'enum' => 'مقدار انتخابی :attribute نامعتبر است. ❌',
    'exists' => 'مقدار انتخابی :attribute نامعتبر است. ❌',
    'extensions' => 'فیلد :attribute باید یکی از پسوندهای زیر را داشته باشد: :values. 🗂️',
    'file' => 'فیلد :attribute باید یک فایل باشد. 📁',
    'filled' => 'فیلد :attribute باید یک مقدار داشته باشد. 📝',
    'gt' => [
        'array' => 'فیلد :attribute باید بیشتر از :value آیتم داشته باشد. ➕',
        'file' => 'فیلد :attribute باید بیشتر از :value کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید بزرگتر از :value باشد. 🔢',
        'string' => 'فیلد :attribute باید بیشتر از :value کاراکتر باشد. 🔠',
    ],
    'gte' => [
        'array' => 'فیلد :attribute باید :value آیتم یا بیشتر داشته باشد. ➕',
        'file' => 'فیلد :attribute باید بزرگتر یا برابر با :value کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید بزرگتر یا برابر با :value باشد. 🔢',
        'string' => 'فیلد :attribute باید بزرگتر یا برابر با :value کاراکتر باشد. 🔠',
    ],
    'hex_color' => 'فیلد :attribute باید یک رنگ هگزادسیمال معتبر باشد. 🎨',
    'image' => 'فیلد :attribute باید یک تصویر باشد. 🖼️',
    'in' => 'مقدار انتخابی :attribute نامعتبر است. ❌',
    'in_array' => 'فیلد :attribute باید در :other وجود داشته باشد. 🔄',
    'integer' => 'فیلد :attribute باید یک عدد صحیح باشد. 🔢',
    'ip' => 'فیلد :attribute باید یک آدرس IP معتبر باشد. 🌐',
    'ipv4' => 'فیلد :attribute باید یک آدرس IPv4 معتبر باشد. 🌍',
    'ipv6' => 'فیلد :attribute باید یک آدرس IPv6 معتبر باشد. 🌍',
    'json' => 'فیلد :attribute باید یک رشته JSON معتبر باشد. 📜',
    'list' => 'فیلد :attribute باید یک لیست باشد. 📋',
    'lowercase' => 'فیلد :attribute باید حروف کوچک باشد. 🔡',
    'lt' => [
        'array' => 'فیلد :attribute باید کمتر از :value آیتم داشته باشد. ➖',
        'file' => 'فیلد :attribute باید کمتر از :value کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید کمتر از :value باشد. 🔢',
        'string' => 'فیلد :attribute باید کمتر از :value کاراکتر باشد. 🔠',
    ],
    'lte' => [
        'array' => 'فیلد :attribute نباید بیشتر از :value آیتم داشته باشد. ➖',
        'file' => 'فیلد :attribute نباید بیشتر از :value کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute نباید بیشتر از :value باشد. 🔢',
        'string' => 'فیلد :attribute نباید بیشتر از :value کاراکتر باشد. 🔠',
    ],
    'mac_address' => 'فیلد :attribute باید یک آدرس MAC معتبر باشد. 🖧',
    'max' => [
        'array' => 'فیلد :attribute نباید بیشتر از :max آیتم داشته باشد. 🚫',
        'file' => 'فیلد :attribute نباید بیشتر از :max کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute نباید بیشتر از :max باشد. 🔢',
        'string' => 'فیلد :attribute نباید بیشتر از :max کاراکتر باشد. 🔠',
    ],
    'max_digits' => 'فیلد :attribute نباید بیشتر از :max رقم داشته باشد. 🔢',
    'mimes' => 'فیلد :attribute باید یک فایل از نوع: :values باشد. 📂',
    'mimetypes' => 'فیلد :attribute باید یک فایل از نوع: :values باشد. 📂',
    'min' => [
        'array' => 'فیلد :attribute باید حداقل :min آیتم داشته باشد. ➖',
        'file' => 'فیلد :attribute باید حداقل :min کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید حداقل :min باشد. 🔢',
        'string' => 'فیلد :attribute باید حداقل :min کاراکتر باشد. 🔠',
    ],
    'min_digits' => 'فیلد :attribute باید حداقل :min رقم داشته باشد. 🔢',
    'missing' => 'فیلد :attribute باید غایب باشد. 🚫',
    'missing_if' => 'فیلد :attribute باید غایب باشد زمانی که :other برابر با :value است. ❌',
    'missing_unless' => 'فیلد :attribute باید غایب باشد مگر اینکه :other برابر با :value باشد. ❌',
    'missing_with' => 'فیلد :attribute باید غایب باشد زمانی که :values موجود است. 🚫',
    'missing_with_all' => 'فیلد :attribute باید غایب باشد زمانی که :values موجود هستند. 🚫',
    'multiple_of' => 'فیلد :attribute باید مضربی از :value باشد. ➗',
    'not_in' => 'مقدار انتخابی :attribute نامعتبر است. ❌',
    'not_regex' => 'فرمت فیلد :attribute نامعتبر است. ⚠️',
    'numeric' => 'فیلد :attribute باید یک عدد باشد. 🔢',
    'password' => [
        'letters' => 'فیلد :attribute باید حداقل یک حرف داشته باشد. 🔤',
        'mixed' => 'فیلد :attribute باید حداقل یک حرف بزرگ و یک حرف کوچک داشته باشد. 🔠🔡',
        'numbers' => 'فیلد :attribute باید حداقل یک عدد داشته باشد. 🔢',
        'symbols' => 'فیلد :attribute باید حداقل یک نماد داشته باشد. 💡',
        'uncompromised' => 'فیلد :attribute که وارد کرده‌اید در یک افشای داده ظاهر شده است. لطفاً از :attribute دیگری استفاده کنید. 🔓',
    ],
    'present' => 'فیلد :attribute باید موجود باشد. ✅',
    'present_if' => 'فیلد :attribute باید موجود باشد زمانی که :other برابر با :value است. ✅',
    'present_unless' => 'فیلد :attribute باید موجود باشد مگر اینکه :other برابر با :value باشد. ✅',
    'present_with' => 'فیلد :attribute باید موجود باشد زمانی که :values موجود است. ✅',
    'present_with_all' => 'فیلد :attribute باید موجود باشد زمانی که :values موجود هستند. ✅',
    'prohibited' => 'فیلد :attribute ممنوع است. 🚫',
    'prohibited_if' => 'فیلد :attribute ممنوع است زمانی که :other برابر با :value است. 🚫',
    'prohibited_if_accepted' => 'فیلد :attribute ممنوع است زمانی که :other پذیرفته شده باشد. 🚫',
    'prohibited_if_declined' => 'فیلد :attribute ممنوع است زمانی که :other رد شده باشد. 🚫',
    'prohibited_unless' => 'فیلد :attribute ممنوع است مگر اینکه :other در :values باشد. 🚫',
    'prohibits' => 'فیلد :attribute از حضور :other جلوگیری می‌کند. 🚫',
    'regex' => 'فرمت فیلد :attribute نامعتبر است. ⚠️',
    'required' => 'فیلد :attribute ضروری است. ✔️',
    'required_array_keys' => 'فیلد :attribute باید ورودی‌هایی برای :values داشته باشد. 📋',
    'required_if' => 'فیلد :attribute ضروری است زمانی که :other برابر با :value است. ✔️',
    'required_if_accepted' => 'فیلد :attribute ضروری است زمانی که :other پذیرفته شده باشد. ✔️',
    'required_if_declined' => 'فیلد :attribute ضروری است زمانی که :other رد شده باشد. ✔️',
    'required_unless' => 'فیلد :attribute ضروری است مگر اینکه :other در :values باشد. ✔️',
    'required_with' => 'فیلد :attribute ضروری است زمانی که :values موجود است. ✔️',
    'required_with_all' => 'فیلد :attribute ضروری است زمانی که :values موجود هستند. ✔️',
    'required_without' => 'فیلد :attribute ضروری است زمانی که :values موجود نیست. ✔️',
    'required_without_all' => 'فیلد :attribute ضروری است زمانی که هیچ‌یک از :values موجود نباشند. ✔️',
    'same' => 'فیلد :attribute باید با :other مطابقت داشته باشد. 🔄',
    'size' => [
        'array' => 'فیلد :attribute باید شامل :size آیتم باشد. 📋',
        'file' => 'فیلد :attribute باید :size کیلوبایت باشد. 💾',
        'numeric' => 'فیلد :attribute باید :size باشد. 🔢',
        'string' => 'فیلد :attribute باید :size کاراکتر باشد. 🔠',
    ],
    'starts_with' => 'فیلد :attribute باید با یکی از موارد زیر شروع شود: :values. ➡️',
    'string' => 'فیلد :attribute باید یک رشته باشد. 🔠',
    'timezone' => 'فیلد :attribute باید یک منطقه زمانی معتبر باشد. 🕰️',
    'unique' => 'فیلد :attribute قبلاً گرفته شده است. 🚫',
    'uploaded' => 'فیلد :attribute بارگذاری نشد. ⚠️',
    'uppercase' => 'فیلد :attribute باید حروف بزرگ باشد. 🔠',
    'url' => 'فیلد :attribute باید یک URL معتبر باشد. 🌐',
    'ulid' => 'فیلد :attribute باید یک ULID معتبر باشد. 🔑',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد. 🔑',

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
            'rule-name' => 'custom-message',
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

    'attributes' => [],

];
