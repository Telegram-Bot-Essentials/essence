<?php

return [
    'main' => [
        'text' => [
            'information' => 'Settings'
                . "\r\n"
                . "\r\nBot Status :botStatus"
                . "\r\nPay With Card :payWithCardStatus"
                . "\r\n"
                . "\r\nPayments Card Number: :paymentCardNumber"
                . "\r\nPayments Card Name: :paymentCardName"
                . "\r\n"
                . "\r\nTransactions chat ID: :transactionsChatId",
            'botIsOffAlert' => 'Bot is currently out of service.',
            'changePaymentCardNumber' => 'Enter new payment card number: ',
            'changePaymentCardName' => 'Enter new payment card name: ',
            'transactionsChatId' => 'Enter new transactions chat ID: ',

            'valueUpdatedSuccessfully' => 'Value updated successfully',
        ],
        'answers' => [
            'paymentCardNumber' => 'Updating payment card number...',
            'paymentCardName' => 'Updating payment card name...',
            'transactionsChatId' => 'Updating transactions chat ID...',

            'botStatusUpdated' => 'Bot Status :newStatus',
            'payWithCardStatusUpdated' => 'Pay with card Status :newStatus'
        ],
        'keys' => [
            'botStatus' => 'Bot Status',
            'payWithCardStatus' => 'Pay with Card',
            'paymentCardNumber' => 'Payment Card Number',
            'paymentCardName' => 'Payment Card Name',
            'transactionsChatId' => 'Transactions Chat ID',
        ]
    ],
    'reply_key' => 'Bot Settings',
];
