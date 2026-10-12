<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Contracts\ResolvesKeyboard;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Admin\AdminPanelKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\MainMenuKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Tests\Fixtures\StepwiseStateAnswer;

/*
 * replyKeyboard() delegates to the bound ResolvesKeyboard. The
 * default builds named menus from config, still honours a state answer's
 * own keyboard, and can be turned off entirely for bots with no reply
 * keyboard.
 */

uses(RefreshDatabase::class);

class KeyboardSupportKey extends ReplyKey
{
    protected function text(): string
    {
        return 'Support';
    }

    public function handle(): void {}
}

class KeyboardReportsKey extends ReplyKey
{
    protected ?string $permission = 'reports.view';

    protected function text(): string
    {
        return 'Reports';
    }

    public function handle(): void {}
}

function keyboardRows(Keyboard $keyboard): array
{
    return $keyboard->toArray()['keyboard'] ?? [];
}

beforeEach(function () {
    stateAnswerBus()->addStateAnswer(StepwiseStateAnswer::class);

    $this->bot = $this->makeBot();
    $this->member = $this->makeBotUser($this->bot, 601);
    $this->admin = $this->makeBotUser($this->bot, 602);
});

it('uses a rebound resolver for every replyKeyboard() call', function () {
    app()->bind(ResolvesKeyboard::class, fn () => new class implements ResolvesKeyboard
    {
        public function for(BotUser $user): Keyboard
        {
            return Keyboard::make()->row(['custom '.$user->telegram_user_peer_id]);
        }
    });

    expect(keyboardRows(replyKeyboard($this->member)))->toBe([['custom 601']]);
});

it('shows the menu named on the user, including ones beyond main and admin', function () {
    config(['tbe-essence.keyboard.menus.support' => [[KeyboardSupportKey::class, MainMenuKey::class]]]);

    $this->member->update(['menu' => 'support']);

    expect(keyboardRows(replyKeyboard($this->member->fresh())))
        ->toBe([['Support', __('tbe::main_menu.reply_key')]]);
});

it('falls back to the main menu for a menu nothing configures', function () {
    config(['tbe-essence.keyboard.menus.main' => [[KeyboardSupportKey::class]]]);

    $this->member->update(['menu' => 'gone']);

    expect(keyboardRows(replyKeyboard($this->member->fresh())))->toBe([['Support']]);
});

it('drops a row whose keys are all hidden', function () {
    config(['tbe-essence.keyboard.menus.main' => [[KeyboardSupportKey::class], [AdminPanelKey::class]]]);

    expect(keyboardRows(replyKeyboard($this->member)))->toBe([['Support']])
        ->and(replyKeyboard($this->member)->toArray())->toMatchArray(['resize_keyboard' => true, 'one_time_keyboard' => true]);
});

it('filters menu keys by the permission they name', function () {
    config(['tbe-essence.keyboard.menus.main' => [[KeyboardSupportKey::class, KeyboardReportsKey::class]]]);
    $this->grant($this->admin, 'reports.view');

    expect(keyboardRows(replyKeyboard($this->member)))->toBe([['Support']])
        ->and(keyboardRows(replyKeyboard($this->admin)))->toBe([['Support', 'Reports']]);
});

it('removes the reply keyboard when keyboards are turned off', function () {
    config(['tbe-essence.keyboard.enabled' => false]);

    expect(replyKeyboard($this->member)->toArray())->toMatchArray(['remove_keyboard' => true]);

    $this->member->changeState(encodeAnswerState('STEPWISE', 'answer', ['step' => 'other']));

    // No automatic Cancel key either.
    expect(replyKeyboard($this->member)->toArray())->toMatchArray(['remove_keyboard' => true]);
});

it('still shows a state answer its own keyboard when keyboards are turned off', function () {
    config(['tbe-essence.keyboard.enabled' => false]);

    $this->member->changeState(encodeAnswerState('STEPWISE', 'answer', ['step' => 'code']));

    expect(keyboardRows(replyKeyboard($this->member)))->toBe([['Skip', 'Cancel']]);
});

it('stores any menu name on the user', function () {
    $this->member->update(['menu' => 'a-menu-the-old-enum-never-had']);

    expect($this->member->fresh()->menu)->toBe('a-menu-the-old-enum-never-had');
});
