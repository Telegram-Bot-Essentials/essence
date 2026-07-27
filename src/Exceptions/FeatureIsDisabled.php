<?php

namespace TelegramBotEssentials\Essence\Exceptions;

use Exception;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class FeatureIsDisabled extends Exception
{
    private ?TelegramResponse $response = null;

    public function __construct(string|TelegramResponse|null $message = null)
    {
        if ($message instanceof TelegramResponse) {
            $this->response = $message;
            $message = $message->text ?? '';
        }

        parent::__construct($message ?? '');
    }

    public function getResponse(): ?TelegramResponse
    {
        return $this->response;
    }
}
