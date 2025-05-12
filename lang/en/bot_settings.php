<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>Settings</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Bot Status</b> :botStatus"
                . "\r\n❔ <b>Pay With Card</b> :payWithCardStatus"
                . "\r\n"
                . "\r\n❔ <b>Payments Card Number:</b> <i>:paymentCardNumber</i>"
                . "\r\n❔ <b>Payments Card Name:</b> <i>:paymentCardName</i>"
                . "\r\n"
                . "\r\n❔ <b>Transactions chat ID:</b> <i>:transactionsChatId</i>",
            'botIsOffAlert' => '❗️ Bot is currently out of service.',
            'changePaymentCardNumber' => '❓ Enter new payment card number: ',
            'changePaymentCardName' => '❓ Enter new payment card name: ',
            'transactionsChatId' => '❓ Enter new transactions chat ID: ',
        ],
        'answers' => [
            'paymentCardNumber' => '⏳ Updating payment card number...',
            'paymentCardName' => '⏳ Updating payment card name...',
            'transactionsChatId' => '⏳ Updating transactions chat ID...',

            'botStatusUpdated' => 'Bot Status :newStatus',
            'payWithCardStatusUpdated' => 'Pay with card Status :newStatus'
        ],
        'keys' => [
            'botStatus' => 'Bot Status :status',
            'payWithCardStatus' => 'Pay with Card :status',
            'paymentCardNumber' => '✏️ Payment Card Number',
            'paymentCardName' => '✏️ Payment Card Name',
            'transactionsChatId' => '✏️ Transactions Chat ID',
        ]
    ],
    'reply_key' => 'Bot Settings ⚙️',
];
