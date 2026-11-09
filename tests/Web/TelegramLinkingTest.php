<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Testing\TestAccount;
use TelegramBotEssentials\Essence\Web\Filament\TelegramAccountsPage;
use TelegramBotEssentials\Essence\Web\LinkCodes;
use TelegramBotEssentials\Essence\Web\Telegram\LinkAccountQuery;
use TelegramBotEssentials\Essence\Web\WebAccounts;

uses(RefreshDatabase::class);

/** Texts the bot sent or put in place of a message, oldest first. */
function linkTexts(): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => preg_match('#/(sendMessage|editMessageText)$#', (string) $pair[0]->url()) === 1)
        ->map(fn ($pair) => (string) $pair[0]['text'])
        ->values()
        ->all();
}

function linkPayload(string $code): string
{
    return '/start '.LinkCodes::PAYLOAD_PREFIX.$code;
}

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->account = TestAccount::query()->create(['name' => 'Shop owner']);
});

it('asks the opening telegram account to confirm before linking', function () {
    $code = app(LinkCodes::class)->create($this->account);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate(linkPayload($code), 7001))->assertOk();

    expect(linkTexts())->toContain(__('tbe-essence::web.link_question', ['account' => 'Shop owner']))
        ->and($this->account->fresh()->linkedPeerIds())->toBe([]);

    $buttons = collect(Http::recorded())->map(fn ($pair) => (string) ($pair[0]['reply_markup'] ?? ''))->implode('');
    expect($buttons)->toContain(LinkAccountQuery::confirmData($code));
});

it('links on confirm, once', function () {
    $code = app(LinkCodes::class)->create($this->account);
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate(linkPayload($code), 7002));

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(LinkAccountQuery::confirmData($code), 7002))->assertOk();
    expect($this->account->fresh()->linkedPeerIds())->toBe([7002])
        ->and(linkTexts())->toContain(__('tbe-essence::web.link_done', ['account' => 'Shop owner']));

    // Another Telegram account pressing the same button later gets nothing.
    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(LinkAccountQuery::confirmData($code), 7003));
    expect($this->account->fresh()->linkedPeerIds())->toBe([7002])
        ->and(linkTexts())->toContain(__('tbe-essence::web.link_expired'));
});

it('links nothing on cancel and spends the code', function () {
    $code = app(LinkCodes::class)->create($this->account);

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(LinkAccountQuery::cancelData($code), 7004));
    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(LinkAccountQuery::confirmData($code), 7004));

    expect($this->account->fresh()->linkedPeerIds())->toBe([])
        ->and(linkTexts())->toContain(__('tbe-essence::web.link_cancelled'), __('tbe-essence::web.link_expired'));
});

it('says an unknown or expired code has expired', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate(linkPayload('nope'), 7005));

    expect(linkTexts())->toContain(__('tbe-essence::web.link_expired'));
});

it('refuses a telegram account another web account links', function () {
    TestAccount::query()->create(['name' => 'Someone else'])->linkTelegram(7006);
    $code = app(LinkCodes::class)->create($this->account);

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate(linkPayload($code), 7006));
    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(LinkAccountQuery::confirmData($code), 7006));

    expect($this->account->fresh()->linkedPeerIds())->toBe([])
        ->and(linkTexts())->toContain(__('tbe-essence::web.link_taken'))
        ->and(linkTexts())->not->toContain(__('tbe-essence::web.link_question', ['account' => 'Shop owner']));
});

it('leaves other deep links alone', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/start c_abc', 7007))->assertOk();

    expect(linkTexts())->not->toContain(__('tbe-essence::web.link_expired'));
});

describe('the telegram accounts page', function () {
    beforeEach(function () {
        Gate::before(fn ($u, $ability) => $ability === 'web.manage' ? true : null);
        $this->panelBot = Bot::factory()->create(['unique_id' => 'linking', 'bot_token' => '1:linking', 'activated_until' => now()->addYear()]);
        Cache::put('tbe:bot-username:'.$this->panelBot->id.':'.md5('1:linking'), 'linking_bot', 60);
        TelegramUser::query()->create(TelegramUser::factory()->raw(['peer_id' => 8001, 'first_name' => 'Main', 'username' => 'main_tg']));
        $this->botUser = BotUser::query()->create(['bot_id' => $this->panelBot->id, 'telegram_user_peer_id' => 8001]);
        $this->account->linkTelegram(8001);
    });

    it('lists the account\'s telegram accounts over a real request', function () {
        Auth::guard(WebAccounts::guard())->login($this->account);

        $this->get('/manage/linking/telegram-accounts')
            ->assertOk()
            ->assertSee('@main_tg')
            ->assertSee(__('tbe-essence::web.link_another'));
    });

    it('sends signed-out visitors away', function () {
        $this->get('/manage/linking/telegram-accounts')->assertRedirect();
    });

    it('makes a deep link into the bot with a working code', function () {
        $this->enterPanel('manage', $this->panelBot, $this->account);

        $page = Livewire::test(TelegramAccountsPage::class)->mountAction('link');
        $url = $page->get('linkUrl');

        expect($url)->toStartWith('https://t.me/linking_bot?start='.LinkCodes::PAYLOAD_PREFIX);
        $code = substr($url, strlen('https://t.me/linking_bot?start='.LinkCodes::PAYLOAD_PREFIX));
        expect(app(LinkCodes::class)->peek($code)?->getAuthIdentifier())->toBe($this->account->id);
    });

    it('keeps the last telegram account of an account without a password', function () {
        $this->enterPanel('manage', $this->panelBot, $this->account);
        $link = $this->account->telegramLinks()->firstOrFail();

        Livewire::test(TelegramAccountsPage::class)->assertTableActionHidden('unlink', $link);
    });

    it('unlinks one of several', function () {
        $this->account->linkTelegram(8002);
        $this->enterPanel('manage', $this->panelBot, $this->account);
        $second = $this->account->telegramLinks()->where('telegram_user_peer_id', 8002)->firstOrFail();

        Livewire::test(TelegramAccountsPage::class)->callTableAction('unlink', $second);

        expect($this->account->fresh()->linkedPeerIds())->toBe([8001]);
    });
});
