<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>تنظیمات</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت ربات</b> :botStatus"
                . "\r\n"
                . "\r\n❔ <b>زبان:</b> :language"
                . "\r\n❔ <b>ارز:</b> :defaultCurrency",
        ],
        'answers' => [
            'botStatusUpdated' => 'وضعیت ربات :newStatus',

            'botLanguage' => 'زبان ربات به :language تغییر یافت',
        ],
        'keys' => [
            'botLanguage' => '🌍 زبان :language',
            'manageGateways' => 'مدیریت درگاه‌ها 💵',
            'manageCurrencies' => 'مدیریت ارز ها 🛠',
            'botStatus' => 'وضعیت ربات :status',
        ]
    ],

    'gateways' => [
        'text' => [
            'information' => '⚙️ <b><i>درگاه‌ها</i></b>'
                . "\r\n"
                . "\r\n❕ درگاهی که می‌خواهید مدیریت کنید را انتخاب کنید",
        ],
        'answers' => [
        ],
        'keys' => [
            'toCard' => 'کارت به کارت :status',
            'zirgozar' => 'زیرگذر :status',
            'zibal' => 'زیبال :status',
            'zarinpal' => 'زرین‌پال :status',
            'idpay' => 'آیدی‌پی :status',
            'nextpay' => 'نکست‌پی :status',
            'nowpayments' => 'NowPayments :status',
            'wallet' => 'کیف پول :status',
        ]
    ],

    'to_card' => [
        'name' => 'کارت به کارت',
        'text' => [
            'information' => '⚙️ <b><i>پرداخت با کارت</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت فعال‌سازی:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>شماره کارت پرداخت:</b> <i>:paymentCardNumber</i>"
                . "\r\n❔ <b>نام کارت پرداخت:</b> <i>:paymentCardName</i>"
                . "\r\n"
                . "\r\n❔ <b>شناسه چت تراکنش‌ها:</b> <i>:transactionsChatId</i>",
            'changePaymentCardNumber' => '❓ شماره کارت جدید را وارد کنید: ',
            'changePaymentCardName' => '❓ نام کارت جدید را وارد کنید: ',
            'transactionsChatId' => '❓ شناسه چت جدید تراکنش‌ها را وارد کنید: ',
        ],
        'answers' => [
            'paymentCardNumber' => '⏳ در حال به‌روزرسانی شماره کارت پرداخت...',
            'paymentCardName' => '⏳ در حال به‌روزرسانی نام کارت پرداخت...',
            'transactionsChatId' => '⏳ در حال به‌روزرسانی شناسه چت تراکنش‌ها...',

            'payWithCardStatusUpdated' => 'وضعیت پرداخت با کارت :newStatus',
        ],
        'keys' => [
            'payWithCardStatus' => 'وضعیت پرداخت با کارت :statusEmoji',
            'paymentCardNumber' => '✏️ شماره کارت پرداخت',
            'paymentCardName' => '✏️ نام کارت پرداخت',
            'transactionsChatId' => '✏️ شناسه چت تراکنش‌ها',
        ]
    ],

    'zibal' => [
        'name' => 'زیبال',
        'text' => [
            'information' => '⚙️ <b><i>زیبال</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت فعال‌سازی:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>زیبال/مرچنت:</b> <tg-spoiler>:zibalMerchant</tg-spoiler>",
            'setMerchant' => '❓ مرچنت جدید زیبال را وارد کنید: '
        ],
        'answers' => [
            'updatingMerchant' => '⏳ در حال به‌روزرسانی مرچنت زیبال...',
        ],
        'keys' => [
            'activation' => 'وضعیت زیبال :statusEmoji',
            'merchant' => '✏️ مرچنت',
        ]
    ],

    'zirgozar' => [
        'name' => 'زیرگذر',
        'text' => [
            'information' => '⚙️ <b><i>زیرگذر</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت فعال‌سازی:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>توکن:</b> <tg-spoiler>:zirgozarToken</tg-spoiler>",
            'setToken' => '❓ توکن جدید زیرگذر را وارد کنید: '
        ],
        'answers' => [
            'updatingToken' => '⏳ در حال به‌روزرسانی توکن زیرگذر...',
        ],
        'keys' => [
            'activation' => 'وضعیت زیرگذر :statusEmoji',
            'token' => '✏️ توکن',
        ]
    ],

    'zarinpal' => [
        'name' => 'زرین‌پال',
        'text' => [
            'information' => '⚙️ <b><i>زرین‌پال</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت فعال‌سازی:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>شناسه مرچنت:</b> <tg-spoiler>:merchantID</tg-spoiler>",
            'setMerchant' => '❓ شناسه مرچنت جدید زرین‌پال را وارد کنید: '
        ],
        'answers' => [
            'updatingToken' => '⏳ در حال به‌روزرسانی شناسه مرچنت زرین‌پال...',
        ],
        'keys' => [
            'activation' => 'وضعیت زرین‌پال :statusEmoji',
            'merchantID' => '✏️ شناسه مرچنت',
        ]
    ],

    'wallet' => [
        'name' => 'کیف پول',
        'text' => [
            'information' => '⚙️ <b><i>کیف پول</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت فعال‌سازی:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>ارز ربات:</b> :botCurrency",
        ],
        'answers' => [
        ],
        'keys' => [
            'activation' => 'وضعیت کیف پول :statusEmoji',
        ]
    ],

    'reply_key' => 'تنظیمات ربات ⚙️',
];
