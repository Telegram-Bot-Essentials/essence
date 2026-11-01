<?php

return [
    'command_description' => 'Approve the groups this bot works in',

    'alerts' => [
        'pending' => 'The bot was added to the group ":title". It stays silent there until you approve it with /groups.',
    ],

    'index' => [
        'text' => 'Groups the bot is in. Press one to approve it, or to take approval back.',
        'empty' => 'The bot is not in any group yet.',
        'approved' => '✅ :title',
        'pending' => '⏳ :title',
    ],
];
