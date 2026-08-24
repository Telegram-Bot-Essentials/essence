<?php

return [
    'text' => [
        'enterResourcesNewField' => "Enter the :resourceName's new :field:",
        'resourceFieldUpdated' => ':resource\'s :field updated successfully.',
    ],

    'status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'notSet' => 'Is not set',
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
        'suspended' => "Suspended 🛑\r\nSuspended at :suspendedDate",
        'notSuspended' => 'Not suspended ✅',
    ],

    'callbackQuery' => [
        'willBeAddedInTheFuture' => '❕ Will be added in the future.',
    ],

    'command' => [
        'notFound' => '❕ Command not found.',
        'helpDescription' => 'Get a list of available commands',
        'availableCommands' => 'Available commands:',
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
        'enterNewValueOfField' => '❓ Enter value for :field:',
        'deleteConfirmationQuestion' => 'Are you sure you want to delete :resource ":resourceName"?',
    ],

    'keys' => [
        'generateInvoiceAndPay' => 'Pay Now 💵 - :price',
        'bunchDeletion' => 'Bunch Deletion 🗑',
        'bunchActivation' => 'Bunch Activation 🔥',
        'delete' => 'Delete 🗑',
        'back' => 'Back 🔙',
    ],

    'lock-keys' => [
        'waitingForFieldUpdate' => 'Waiting for :field change',
    ],

    'answers' => [
        'updatedResourceField' => 'Updating ":resource"s :field...',
        'resourceFieldUpdatedSuccessfully' => ':resource Updated successfully.',
        'resourceDeletedSuccessfully' => ':resource Deleted successfully.',
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
