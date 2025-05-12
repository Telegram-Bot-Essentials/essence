<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>تنظیمات</i></b>'
                . "\r\n"
                . "\r\n❔ <b>وضعیت ربات</b> :botStatus"
                . "\r\n❔ <b>پرداخت با کارت</b> :payWithCardStatus"
                . "\r\n"
                . "\r\n❔ <b>شماره کارت پرداخت:</b> <i>:paymentCardNumber</i>"
                . "\r\n❔ <b>نام کارت پرداخت:</b> <i>:paymentCardName</i>"
                . "\r\n"
                . "\r\n❔ <b>شناسه چت تراکنشها:</b> <i>:transactionsChatId</i>",
            'changePaymentCardNumber' => '❓ شماره کارت جدید را وارد کنید: ',
            'changePaymentCardName' => '❓ نام کارت جدید را وارد کنید: ',
            'transactionsChatId' => '❓ شناسه چت جدید تراکنشها را وارد کنید: ',
        ],
        'answers' => [
            'paymentCardNumber' => '⏳ در حال بهروزرسانی شماره کارت پرداخت...',
            'paymentCardName' => '⏳ در حال بهروزرسانی نام کارت پرداخت...',
            'transactionsChatId' => '⏳ در حال بهروزرسانی شناسه چت تراکنشها...',

            'botStatusUpdated' => 'وضعیت ربات :newStatus',
            'payWithCardStatusUpdated' => 'وضعیت پرداخت با کارت :newStatus',

            'botLanguage' => 'زبان ربات به :language تغییر یافت',
        ],
        'keys' => [
            'botLanguage' => '🌍 زبان: :language',
            'botStatus' => 'وضعیت ربات :status',
            'payWithCardStatus' => 'پرداخت با کارت :status',
            'paymentCardNumber' => '✏️ شماره کارت پرداخت',
            'paymentCardName' => '✏️ نام کارت پرداخت',
            'transactionsChatId' => '✏️ شناسه چت تراکنشها',
        ]
    ],
    'reply_key' => 'تنظیمات ربات ⚙️',
];
