<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Testing\TestCase;

uses(RefreshDatabase::class);

function stateBotUser(TestCase $test, int $peerId, ?string $state): BotUser
{
    $bot = test()->makeBot();

    return test()->makeBotUser($bot, $peerId, ['state' => $state]);
}

it('stores a state far past the old 255-character limit', function () {
    $state = encodeAnswerState('FORM', 'answer', ['answers' => ['description' => str_repeat('ب', 3000)]]);

    $user = stateBotUser($this, 1001, $state);

    expect(strlen($state))->toBeGreaterThan(255)
        ->and($user->privateConversation()->state)->toBe($state)
        ->and(decodeAnswerState($user->privateConversation()->state)['params']['answers']['description'])->toBe(str_repeat('ب', 3000));
});

it('merges params into an existing state, keeping their types', function () {
    $user = stateBotUser($this, 1002, encodeAnswerState('FORM', 'answer', ['step' => 'code']));

    $user->privateConversation()->addParamToState(['lastPage' => 3]);

    expect(decodeAnswerState($user->privateConversation()->state))->toBe([
        'type' => 'FORM',
        'method' => 'answer',
        'params' => ['step' => 'code', 'lastPage' => 3],
    ]);
});

it('leaves a user without a state alone when params are added', function () {
    $user = stateBotUser($this, 1003, null);

    $user->privateConversation()->addParamToState(['lastPage' => 3]);

    expect($user->privateConversation()->state)->toBeNull();
});
