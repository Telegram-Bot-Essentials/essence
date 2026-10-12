<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

/**
 * Reproduces the shape of a state-answer handler that clears its own state
 * and then, synchronously and for the same user, does something that runs
 * through Webhook::runForUser() - a payment gateway's auto-accept firing an
 * InvoicePaid listener via Invoice::markAsPaid() is the real-world case this
 * stands in for.
 */
class RunsForUserStateAnswer extends StateAnswer
{
    protected string $type = 'RUNS_FOR_USER';

    public function answer(): void
    {
        wHook()->conversation()->changeState();

        wHook()->runForUser(wHook()->user(), fn () => null);
    }
}
