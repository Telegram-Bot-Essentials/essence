<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        ->and($user->fresh()->state)->toBe($state)
        ->and(decodeAnswerState($user->fresh()->state)['params']['answers']['description'])->toBe(str_repeat('ب', 3000));
});

it('merges params into an existing state, keeping their types', function () {
    $user = stateBotUser($this, 1002, encodeAnswerState('FORM', 'answer', ['step' => 'code']));

    $user->addParamToState(['lastPage' => 3]);

    expect(decodeAnswerState($user->fresh()->state))->toBe([
        'type' => 'FORM',
        'method' => 'answer',
        'params' => ['step' => 'code', 'lastPage' => 3],
    ]);
});

it('leaves a user without a state alone when params are added', function () {
    $user = stateBotUser($this, 1003, null);

    $user->addParamToState(['lastPage' => 3]);

    expect($user->fresh()->state)->toBeNull();
});

it('converts states stored in the legacy query format to JSON in the migration', function () {
    $legacy = stateBotUser($this, 1004, null);
    $json = stateBotUser($this, 1005, null);
    $junk = stateBotUser($this, 1006, null);
    $idle = stateBotUser($this, 1007, null);

    DB::table('bot_users')->where('id', $legacy->id)->update(['state' => 'OFFERS#captureOptionalField?offer=12&field=max_price&lastPage=2&message_meta=90']);
    DB::table('bot_users')->where('id', $json->id)->update(['state' => encodeAnswerState('FORM', 'answer', ['step' => 'x'])]);
    DB::table('bot_users')->where('id', $junk->id)->update(['state' => 'test']);

    (require __DIR__.'/../../database/migrations/2026_09_19_120000_widen_bot_users_state_to_json_text.php')->up();

    expect(decodeAnswerState($legacy->fresh()->state))->toBe([
        'type' => 'OFFERS',
        'method' => 'captureOptionalField',
        'params' => ['offer' => '12', 'field' => 'max_price', 'lastPage' => '2', 'message_meta' => '90'],
    ])
        ->and($json->fresh()->state)->toBe(encodeAnswerState('FORM', 'answer', ['step' => 'x']))
        ->and(decodeAnswerState($junk->fresh()->state)['type'])->toBe('test')
        ->and($idle->fresh()->state)->toBeNull();
});
