<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>Settings</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Bot status:</b> :botStatus"
                ."\r\n"
                ."\r\n❔ <b>Language:</b> :language"
                ."\r\n❔ <b>Currency:</b> :defaultCurrency",
        ],
        'answers' => [
            'botStatusUpdated' => 'Bot Status :newStatus',

            'botLanguage' => 'Bot language changed to :language',
        ],
        'keys' => [
            'botLanguage' => '🌍 Language :language',
            'manageGateways' => 'Manage gateways 💵',
            'manageCurrencies' => 'Manage currencies 🛠',
            'botStatus' => 'Bot status :status',
        ],
    ],

    'gateways' => [
        'text' => [
            'information' => '⚙️ <b><i>Gateways</i></b>'
                ."\r\n"
                ."\r\n❕ Pick a gateway to manage",
        ],
        'answers' => [
        ],
        'keys' => [
            'toCard' => 'To Card :status',
            'zibal' => 'Zibal :status',
            'zarinpal' => 'Zarinpal :status',
            'idpay' => 'IDPay :status',
            'nextpay' => 'NextPay :status',
            'nowpayments' => 'NowPayments :status',
            'wallet' => 'Wallet :status',
        ],
    ],

    'to_card' => [
        'name' => 'To Card',
        'text' => [
            'information' => '⚙️ <b><i>Pay with card</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Activation status:</b> :activationStatus"
                ."\r\n"
                ."\r\n❔ <b>Payment card number:</b> <i>:paymentCardNumber</i>"
                ."\r\n❔ <b>Payment card name:</b> <i>:paymentCardName</i>"
                ."\r\n"
                ."\r\n❔ <b>Transactions chat ID:</b> <i>:transactionsChatId</i>",
            'changePaymentCardNumber' => '❓ Send the new payment card number:',
            'changePaymentCardName' => '❓ Send the new payment card name:',
            'transactionsChatId' => '❓ Send the new transactions chat ID:',
        ],
        'answers' => [
            'paymentCardNumber' => '⏳ Updating the payment card number…',
            'paymentCardName' => '⏳ Updating the payment card name…',
            'transactionsChatId' => '⏳ Updating the transactions chat ID…',

            'payWithCardStatusUpdated' => 'Pay with card Status :newStatus',
        ],
        'keys' => [
            'payWithCardStatus' => 'Pay with card status :statusEmoji',
            'paymentCardNumber' => '✏️ Payment card number',
            'paymentCardName' => '✏️ Payment card name',
            'transactionsChatId' => '✏️ Transactions chat ID',
        ],
    ],

    'zibal' => [
        'name' => 'Zibal',
        'text' => [
            'information' => '⚙️ <b><i>Zibal</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Activation status:</b> :activationStatus"
                ."\r\n"
                ."\r\n❔ <b>Zibal/Merchant:</b> <tg-spoiler>:zibalMerchant</tg-spoiler>",
            'setMerchant' => '❓ Send the new Zibal merchant:',
        ],
        'answers' => [
            'updatingMerchant' => '⏳ Updating the Zibal merchant…',
        ],
        'keys' => [
            'activation' => 'Zibal status :statusEmoji',
            'merchant' => '✏️ Merchant',
        ],
    ],

    'zarinpal' => [
        'name' => 'Zarinpal',
        'text' => [
            'information' => '⚙️ <b><i>Zarinpal</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Activation status:</b> :activationStatus"
                ."\r\n"
                ."\r\n❔ <b>Merchant ID:</b> <tg-spoiler>:merchantID</tg-spoiler>",
            'setMerchant' => '❓ Send the new Zarinpal merchant ID:',
        ],
        'answers' => [
            'updatingToken' => '⏳ Updating the Zarinpal merchant ID…',
        ],
        'keys' => [
            'activation' => 'Zarinpal status :statusEmoji',
            'merchantID' => '✏️ Merchant ID',
        ],
    ],

    'wallet' => [
        'name' => 'Wallet',
        'text' => [
            'information' => '⚙️ <b><i>Wallet</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Activation status:</b> :activationStatus"
                ."\r\n"
                ."\r\n❔ <b>Bot currency:</b> :botCurrency",
        ],
        'answers' => [
        ],
        'keys' => [
            'activation' => 'Wallet status :statusEmoji',
        ],
    ],

    'reply_key' => 'Bot Settings ⚙️',
];
