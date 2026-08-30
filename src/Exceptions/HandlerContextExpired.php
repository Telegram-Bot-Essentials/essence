<?php

namespace TelegramBotEssentials\Essence\Exceptions;

use Exception;

/**
 * Thrown when a handler needs a MessageMeta or StateData row that has been
 * pruned out from under a resumed flow - e.g. a user starts a multi-step
 * admin action, walks away past the pruning window, then sends the awaited
 * message. ExceptionHandler catches this and tells the user the step
 * expired instead of letting a null dereference crash the worker.
 */
class HandlerContextExpired extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: (string) __('tbe::general.alerts.contextExpired'));
    }
}
