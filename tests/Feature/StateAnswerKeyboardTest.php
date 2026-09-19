<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Tests\Fixtures\StepwiseStateAnswer;

uses(RefreshDatabase::class);

beforeEach(function () {
    stateAnswerBus()->addStateAnswer(StepwiseStateAnswer::class);
    StepwiseStateAnswer::$handled = [];

    $this->bot = $this->makeBot();
    $this->makeBotUser($this->bot, 777);
});

function stepwiseState(string $step): string
{
    return encodeAnswerState('STEPWISE', 'answer', ['step' => $step]);
}

it('shows the keyboard the active state answer asks for', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 777))->assertOk();
    $user = wHook()->user();
    $user->changeState(stepwiseState('code'));

    $rows = $user->getKeyboard()->toArray()['keyboard'];

    expect($rows)->toBe([['Skip', 'Cancel']]);
});

it('falls back to the lone Cancel key when the state answer keeps the default', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 777))->assertOk();
    $user = wHook()->user();
    $user->changeState(stepwiseState('other'));

    expect($user->getKeyboard()->toArray()['keyboard'])->toBe([[__('tbe::cancel_process.reply_key')]]);
});

it('falls back to the lone Cancel key for a state nothing is registered for', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('hi', peerId: 777))->assertOk();
    $user = wHook()->user();
    $user->changeState(encodeAnswerState('NOT_REGISTERED', 'answer'));

    expect($user->getKeyboard()->toArray()['keyboard'])->toBe([[__('tbe::cancel_process.reply_key')]]);
});

it('lets a state answer decide per step which message contents it accepts', function () {
    $this->bot->botUsers()->where('telegram_user_peer_id', 777)->sole()->changeState(stepwiseState('text'));
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('typed answer', peerId: 777))->assertOk();
    expect(StepwiseStateAnswer::$handled)->toBe(['text']);

    StepwiseStateAnswer::$handled = [];
    $this->bot->botUsers()->where('telegram_user_peer_id', 777)->sole()->changeState(stepwiseState('photo'));
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('typed where a photo is wanted', peerId: 777))->assertOk();
    expect(StepwiseStateAnswer::$handled)->toBe([]);
});
