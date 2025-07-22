<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>Settings</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Bot Status</b> :botStatus"
                . "\r\n"
                . "\r\n❔ <b>Language:</b> :language"
                . "\r\n❔ <b>Currency:</b> :defaultCurrency",
        ],
        'answers' => [
            'botStatusUpdated' => 'Bot Status :newStatus',

            'botLanguage' => 'Bot language changed to :language',
        ],
        'keys' => [
            'botLanguage' => '🌍 Language :language',
            'manageGateways' => 'Manage Gateways 💵',
            'manageCurrencies' => 'Manage Currencies 🛠',
            'botStatus' => 'Bot Status :status',
        ]
    ],

    'gateways' => [
        'text' => [
            'information' => '⚙️ <b><i>Gateways</i></b>'
                . "\r\n"
                . "\r\n❕ Choose the gateways you want to manage",
        ],
        'answers' => [
        ],
        'keys' => [
            'toCard' => 'To Card :status',
            'zirgozar' => 'Zirgozar :status',
            'zibal' => 'Zibal :status',
            'zarinpal' => 'Zarinpal :status',
            'idpay' => 'IDPay :status',
            'nextpay' => 'NextPay :status',
            'nowpayments' => 'NowPayments :status',
            'wallet' => 'Wallet :status',
        ]
    ],

    'to_card' => [
        'name' => 'To Card',
        'text' => [
            'information' => '⚙️ <b><i>Pay With Card</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Activation Status:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>Payments Card Number:</b> <i>:paymentCardNumber</i>"
                . "\r\n❔ <b>Payments Card Name:</b> <i>:paymentCardName</i>"
                . "\r\n"
                . "\r\n❔ <b>Transactions chat ID:</b> <i>:transactionsChatId</i>",
            'changePaymentCardNumber' => '❓ Enter new payment card number: ',
            'changePaymentCardName' => '❓ Enter new payment card name: ',
            'transactionsChatId' => '❓ Enter new transactions chat ID: ',
        ],
        'answers' => [
            'paymentCardNumber' => '⏳ Updating payment card number...',
            'paymentCardName' => '⏳ Updating payment card name...',
            'transactionsChatId' => '⏳ Updating transactions chat ID...',

            'payWithCardStatusUpdated' => 'Pay with card Status :newStatus',
        ],
        'keys' => [
            'payWithCardStatus' => 'Pay with Card Status :statusEmoji',
            'paymentCardNumber' => '✏️ Payment Card Number',
            'paymentCardName' => '✏️ Payment Card Name',
            'transactionsChatId' => '✏️ Transactions Chat ID',
        ]
    ],

    'zibal' => [
        'name' => 'Zibal',
        'text' => [
            'information' => '⚙️ <b><i>Zibal</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Activation Status:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>Zibal/Merchant:</b> <tg-spoiler>:zibalMerchant</tg-spoiler>",
            'setMerchant' => '❓ Enter new zibal/merchant: '
        ],
        'answers' => [
            'updatingMerchant' => '⏳ Updating zibal/merchant...',
        ],
        'keys' => [
            'activation' => 'Zibal Status :statusEmoji',
            'merchant' => '✏️ Merchant',
        ]
    ],

    'zirgozar' => [
        'name' => 'Zirgozar',
        'text' => [
            'information' => '⚙️ <b><i>Zirgozar</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Activation Status:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>Token:</b> <tg-spoiler>:zirgozarToken</tg-spoiler>",
            'setToken' => '❓ Enter new zirgozar/token: '
        ],
        'answers' => [
            'updatingToken' => '⏳ Updating zirgozar/token...',
        ],
        'keys' => [
            'activation' => 'Zirgozar Status :statusEmoji',
            'token' => '✏️ Token',
        ]
    ],

    'zarinpal' => [
        'name' => 'Zarinpal',
        'text' => [
            'information' => '⚙️ <b><i>Zarinpal</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Activation Status:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>Merchant ID:</b> <tg-spoiler>:merchantID</tg-spoiler>",
            'setMerchant' => '❓ Enter new zarinpal/merchantID: '
        ],
        'answers' => [
            'updatingToken' => '⏳ Updating zarinpal/merchantID...',
        ],
        'keys' => [
            'activation' => 'Zarinpal Status :statusEmoji',
            'merchantID' => '✏️ Merchant ID',
        ]
    ],

    'wallet' => [
        'name' => 'Wallet',
        'text' => [
            'information' => '⚙️ <b><i>Wallet</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Activation Status:</b> :activationStatus"
                . "\r\n"
                . "\r\n❔ <b>Bot currency:</b> :botCurrency",
        ],
        'answers' => [
        ],
        'keys' => [
            'activation' => 'Wallet Status :statusEmoji',
        ]
    ],

    'reply_key' => 'Bot Settings ⚙️',
];
