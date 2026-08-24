<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>Admins</i></b>'
                ."\r\n"
                ."\r\n❔ <b>Bot Owner:</b> <i>:botOwner</i>"
                ."\r\n❔ <b>Admin Count:</b> <i>:adminCount</i>"
                ."\r\n"
                ."\r\nYou can manage bot admins by options in the below 👇",
            'enterNewAdminId' => '❓ Enter new admin\'s Telegram Username or Peer ID: ',
            'adminAddedSuccessfully' => '✅ Admin added successfully.',
        ],
        'answers' => [
            'addingNewAdmin' => 'Adding new admin...',
            'ownerInfo' => ':ownerName is owner of this bot from date :fromDate',
            'adminRemoved' => 'Admin ":adminName" removed successfully.',
        ],
        'keys' => [
            'addNewAdmin' => 'Add new Admin ➕',
            'removeAdmin' => ':adminName 🗑',
            'owner' => 'Owner - :ownerName 👑',
        ],
        'lock-keys' => [
            'addingNewAdmin' => 'Adding new admin',
        ],
    ],
    'reply_key' => 'Bot Admins 🧑‍💻',
];
