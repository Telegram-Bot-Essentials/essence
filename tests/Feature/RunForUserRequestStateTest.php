<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Tests\Fixtures\RunsForUserStateAnswer;

uses(RefreshDatabase::class);

/*
 * Regression: a state-answer handler that clears its own state and then,
 * for the same user, does something that goes through Webhook::runForUser()
 * (a payment auto-accept firing InvoicePaid via Invoice::markAsPaid() is the
 * real case) used to leave wHook()->requestState() null afterward, because
 * runForUser()'s restore step re-derived it from the now-mutated user object
 * instead of putting back what was there before the swap.
 * TelegramWebhookController::processUpdate() reads requestState() right
 * after the handler returns, to construct BotStateAnswerHandled - a null
 * there is a TypeError, since $state isn't nullable.
 */
it('does not lose requestState when a handler runs a callback for its own user', function () {
    stateAnswerBus()->addStateAnswer(RunsForUserStateAnswer::class);

    $bot = $this->makeBot();
    $this->makeBotUser($bot, 777)->changeState(encodeAnswerState('RUNS_FOR_USER', 'answer'));

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('go', peerId: 777))
        ->assertOk();
});
