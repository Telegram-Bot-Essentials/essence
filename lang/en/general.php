<?php

return [
    'text' => [
        "enterResourcesNewField" => "Enter the :resourceName's new :field:",
        "resourceFieldUpdated" => ':resource\'s :field updated successfully.',
    ],

    'status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'notSet' => 'Is not set',
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
        'suspended' => "Suspended 🛑\r\nSuspended at :suspendedDate",
        'notSuspended' => 'Not suspended ✅',
    ],

    'callbackQuery' => [
        'willBeAddedInTheFuture' => '❕ Will be added in the future.',
    ],

    'alerts' => [
        'unableToActivateAttributeMissing' => 'Unable to activate, required attribute :attribute is missing or empty.',
        'unableToSetDoneAttributeMissing' => 'Required attribute \':attribute\' is missing or empty.',
        'botIsOff' => '❗️ Bot is currently out of service.',
        'disabledFeature' => '❗️ This feature (:feature) is currently Disabled.',
        'invalidPageNumber' => '❗️ Target page number is invalid.',
        'samePageNumber' => '❗️ Current page number is the same as the target page number.',
        'outOfBoundPageNumber' => '❗️ Target page number is out of bounds.',
        'notFound' => '❗️ Requested :resource not found.',
        'requestIsInvalid' => '❗️ Request is invalid, please use reply keyboard.',
    ],

    'messages' => [
        'valueUpdatedSuccessfully' => '❕ Value updated successfully ✅',
    ],

    'keys' => [
        'generateInvoiceAndPay' => 'Pay Now 💵 - :price$',
        'bunchDeletion' => "Bunch Deletion 🗑",
        'bunchActivation' => "Bunch Activation 🔥",
        'delete' => 'Delete 🗑',
        'back' => 'Back 🔙',
    ],

    'lock-keys' => [
        'waitingForFieldUpdate' => "Waiting for :field change",
    ],

    'answers' => [
        'updatedResourceField' => 'Updating ":resource"s :field...',
        'resourceFieldUpdatedSuccessfully' => ':resource Updated successfully.',
    ],

    'roles' => [
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'member' => 'Member',
    ],
];
