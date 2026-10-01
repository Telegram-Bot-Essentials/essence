<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>Admins</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Bot owner:</b> <i>:botOwner</i>"
                ."\r\n❔ <b>Admins:</b> <i>:adminCount</i>"
                ."\r\n"
                ."\r\nUse the buttons below to manage the bot's admins 👇",
            'enterNewAdminId' => '❓ Send the new admin\'s Telegram username or ID:',
            'adminAddedSuccessfully' => '✅ Admin added.',
        ],
        'answers' => [
            'addingNewAdmin' => 'Adding a new admin…',
            'ownerInfo' => ':ownerName has owned this bot since :fromDate',
            'adminRemoved' => 'Admin ":adminName" removed.',
        ],
        'keys' => [
            'addNewAdmin' => 'Add new admin ➕',
            'removeAdmin' => ':adminName 🗑',
            'owner' => 'Owner - :ownerName 👑',
        ],
        'lock-keys' => [
            'addingNewAdmin' => 'Adding new admin',
        ],
    ],
    'reply_key' => 'Bot Admins 🧑‍💻',
];
