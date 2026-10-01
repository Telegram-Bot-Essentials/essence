<?php

return [
    'text' => [
        'enterResourcesNewField' => 'Send the new :field for :resourceName:',
        'resourceFieldUpdated' => ':resource\'s :field was updated.',
    ],

    'status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'notSet' => 'Not set',
        'unavailable' => 'Unavailable',
        'enabledEmoji' => '✅',
        'disabledEmoji' => '🛑',
        'xEmoji' => '❌',
        'pendingEmoji' => '⏳',
        'addEmoji' => '➕',
        'removeEmoji' => '➖',
        'activated' => 'Activated',
        'deactivated' => 'Deactivated',
        'removedFrom' => 'removed from',
        'addedTo' => 'added to',
        'suspended' => "Suspended 🛑\r\nSince :suspendedDate",
        'notSuspended' => 'Not suspended ✅',
    ],

    'callbackQuery' => [
        'willBeAddedInTheFuture' => '❕ Coming soon.',
    ],

    'command' => [
        'notFound' => '❕ That command doesn\'t exist.',
        'helpDescription' => 'Show the available commands',
        'availableCommands' => 'Available commands:',
    ],

    'alerts' => [
        'unableToActivateAttributeMissing' => 'Can\'t activate yet: :attribute is missing or empty.',
        'unableToSetDoneAttributeMissing' => ':attribute is missing or empty.',
        'botIsOff' => '❗️ The bot is currently out of service.',
        'disabledFeature' => '❗️ :feature is currently disabled.',
        'invalidPageNumber' => '❗️ That page number isn\'t valid.',
        'samePageNumber' => '❗️ You\'re already on that page.',
        'outOfBoundPageNumber' => '❗️ That page doesn\'t exist.',
        'notFound' => '❗️ Couldn\'t find that :resource.',
        'requestIsInvalid' => '❗️ I didn\'t get that. Please use the keyboard buttons.',
        'contextExpired' => '⏳ This step expired — please start again from the menu.',
    ],

    'messages' => [
        'valueUpdatedSuccessfully' => '✅ Saved.',
        'enterNewValueOfField' => '❓ Send the new value for :field:',
        'deleteConfirmationQuestion' => 'Are you sure you want to delete :resource ":resourceName"?',
    ],

    'keys' => [
        'generateInvoiceAndPay' => 'Pay Now 💵 - :price',
        'bunchDeletion' => 'Bulk delete 🗑',
        'bunchActivation' => 'Bulk activate 🔥',
        'delete' => 'Delete 🗑',
        'back' => 'Back 🔙',
    ],

    'lock-keys' => [
        'waitingForFieldUpdate' => 'Waiting for the new :field',
    ],

    'answers' => [
        'updatedResourceField' => 'Updating the :field of ":resource"…',
        'resourceFieldUpdatedSuccessfully' => ':resource updated.',
        'resourceDeletedSuccessfully' => ':resource deleted.',
    ],

    'roles' => [
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'member' => 'Member',
    ],

    'intervals' => [
        'year' => 'year',
        'month' => 'month',
        'week' => 'week',
        'day' => 'day',
        'hour' => 'hour',
        'minute' => 'minute',
        'second' => 'second',
    ],
];
