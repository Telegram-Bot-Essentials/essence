<?php

namespace Elyar\TelegramBotEssentials\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeatureIsDisabled extends Exception
{
    public function report(Throwable $e): void
    {
        Log::error($e->getMessage());
    }
}
