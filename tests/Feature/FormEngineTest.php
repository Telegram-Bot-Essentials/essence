<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Forms\FormState;
use TelegramBotEssentials\Essence\Tests\Fixtures\SafetyNetForm;
use TelegramBotEssentials\Essence\Tests\Fixtures\SampleForm;
use TelegramBotEssentials\Essence\Tests\Fixtures\SkippableRunForm;

uses(RefreshDatabase::class);

const FORM_PEER = 900;

beforeEach(function () {
    formRegistry()->addForm(SampleForm::class);
    formRegistry()->addForm(SafetyNetForm::class);
    formRegistry()->addForm(SkippableRunForm::class);
    SampleForm::$completed = null;
    SampleForm::$cancelled = [];

    $this->bot = $this->makeBot();
    $this->makeBotUser($this->bot, FORM_PEER);

    // Every sendMessage answers with a fresh message id, like Telegram. And
    // like Telegram, editing a message that carries a reply keyboard is
    // refused ("message can't be edited"): such an edit is recorded as a
    // violation, which every test asserts is empty.
    $this->keyboardMessages = [];
    $this->editViolations = [];
    $test = $this;
    $counter = 1000;
    $factory = new Factory;
    $factory->fake(function ($request) use (&$counter, $test) {
        $url = (string) $request->url();

        if (str_ends_with($url, '/sendMessage')) {
            $id = ++$counter;
            $markup = is_string($request['reply_markup'] ?? null) ? $request['reply_markup'] : json_encode($request['reply_markup'] ?? '');

            if (str_contains((string) $markup, '"keyboard"') && ! str_contains((string) $markup, 'inline_keyboard')) {
                $test->keyboardMessages[$id] = true;
            }

            return Http::response(['ok' => true, 'result' => [
                'message_id' => $id,
                'date' => time(),
                'chat' => ['id' => $request['chat_id'], 'type' => 'private'],
                'text' => $request['text'] ?? '',
            ]]);
        }

        if (str_ends_with($url, '/editMessageText') && isset($test->keyboardMessages[$request['message_id']])) {
            $test->editViolations[] = $request['message_id'];

            return Http::response(['ok' => false, 'error_code' => 400, 'description' => "Bad Request: message can't be edited"], 400);
        }

        return Http::response(['ok' => true, 'result' => true]);
    });
    Http::swap($factory);
});

afterEach(function () {
    expect($this->editViolations)->toBe([], 'the form tried to edit a message that carries a reply keyboard')
        ->and(tgCalls('deleteMessage')->all())->toBe([], 'the form deleted a message');
});

/** Recorded Telegram calls of one API method, oldest first. */
function tgCalls(string $method): Collection
{
    return Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/'.$method))
        ->map(fn ($pair) => $pair[0]->data())
        ->values();
}

/** The texts of the last few messages sent: a step is a prompt plus a keyboard message. */
function sentTexts(int $last = 4): string
{
    return tgCalls('sendMessage')->slice(-$last)->pluck('text')->join("\n---\n");
}

function tgMarkup(array $call): array
{
    $markup = $call['reply_markup'] ?? [];

    return is_string($markup) ? json_decode($markup, true) : $markup;
}

function formStateNow(): ?FormState
{
    return FormState::fromStateString(test()->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state);
}

/** The buttons of the reply keyboard currently showing: the last message that carried one. */
function keyLabels(): array
{
    $markup = tgCalls('sendMessage')->map(fn ($call) => tgMarkup($call))->last(fn ($markup) => isset($markup['keyboard']));

    return collect($markup['keyboard'] ?? [])->flatten()->all();
}

function startSample(array $ctx = ['lastPage' => 2]): void
{
    // The start comes from a button press, as in real use.
    test()->postWebhookUpdate(test()->bot, test()->makeCallbackQueryUpdate('X#y', peerId: FORM_PEER))->assertOk();
    SampleForm::start($ctx);
}

function say(string $text): void
{
    test()->postWebhookUpdate(test()->bot, test()->makeMessageUpdate($text, peerId: FORM_PEER))->assertOk();
}

function label(string $key): string
{
    return __('tbe::forms.buttons.'.$key);
}

/** Walks the form up to the summary with a percentage offer. */
function fillSample(): void
{
    startSample();
    say('SUMMER');
    say('Percentage');
    say('20');
    say('50');
    test()->postWebhookUpdate(test()->bot, test()->makeCallbackQueryUpdate('FORM#pick?4&c3', peerId: FORM_PEER))->assertOk();
}

it('starts on the first step, locks the message it came from and offers only Cancel', function () {
    startSample();

    expect(formStateNow()->step)->toBe('code')
        ->and(formStateNow()->ctx)->toBe(['lastPage' => 2])
        ->and(formStateNow()->meta)->not->toBeNull()
        ->and(sentTexts())->toContain('Code')->toContain('Required')
        ->and(keyLabels())->toBe([__('tbe::cancel_process.reply_key')])
        ->and(tgMarkup(tgCalls('sendMessage')->last())['input_field_placeholder'])->toBe('SUMMER10');

    // The starting message is locked in place.
    expect(tgCalls('editMessageReplyMarkup'))->not->toBeEmpty();
});

it('sends each prompt with its own reply keyboard and never touches it again', function () {
    startSample();

    $prompt = tgCalls('sendMessage')->last();

    expect($prompt['text'])->toContain('Code')->toContain('Required')
        ->and(tgMarkup($prompt))->toHaveKey('keyboard');

    say('SUMMER');

    expect(tgMarkup(tgCalls('sendMessage')->last()))->toHaveKey('keyboard')
        ->and(tgCalls('editMessageText')->all())->toBe([]);
});

it('answers back with what it understood before asking the next question', function () {
    startSample();

    say('SUMMER');

    [$echo, $next] = tgCalls('sendMessage')->slice(-2)->values()->all();
    expect(formStateNow()->answers)->toBe(['code' => 'SUMMER'])
        ->and(formStateNow()->step)->toBe('type')
        ->and($echo['text'])->toContain('Code')->toContain('➜')->toContain('SUMMER')
        ->and($next['text'])->toContain('Type')
        ->and(keyLabels())->toContain('Percentage', 'Fixed amount', label('back'));
});

it('rejects an invalid answer and stays on the step', function () {
    startSample();

    say('THIS-CODE-IS-FAR-TOO-LONG');

    expect(formStateNow()->step)->toBe('code')
        ->and(formStateNow()->answers)->toBe([])
        ->and(tgCalls('sendMessage')->last()['text'])->toContain('10');
});

it('rejects a text that is not one of a static choice options', function () {
    startSample();
    say('SUMMER');

    say('Nonsense');

    expect(formStateNow()->step)->toBe('type')
        ->and(tgCalls('sendMessage')->last()['text'])->toBe(__('tbe::forms.errors.invalidChoice'));
});

it('skips a step whose condition does not hold', function () {
    startSample();
    say('SUMMER');
    say('Fixed amount');
    say('5');

    expect(formStateNow()->step)->toBe('category');
});

it('offers Finish once only optional steps are left, and Finish skips them and shows the summary', function () {
    test()->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('X#y', peerId: FORM_PEER))->assertOk();
    SkippableRunForm::start();

    expect(keyLabels())->not->toContain(label('finish'));

    say('Ada');

    expect(keyLabels())->toContain(label('finish'), label('skip'));

    say(label('finish'));

    expect(formStateNow()->step)->toBe(FormState::CONFIRM)
        ->and(formStateNow()->answers)->toBe(['name' => 'Ada', 'first' => null, 'second' => null, 'third' => null])
        ->and(tgCalls('sendMessage')->last(fn ($call) => str_contains($call['text'], 'Ada'))['text'])->toContain('Ada')
        ->and(keyLabels())->toContain(label('confirm'), label('back'))
        ->and(keyLabels())->not->toContain(label('finish'));

    say(label('confirm'));

    expect($this->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state)->toBeNull()
        ->and(sentTexts())->toContain('Done');
});

it('keeps answers already given when Finish skips the rest', function () {
    test()->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('X#y', peerId: FORM_PEER))->assertOk();
    SkippableRunForm::start();
    say('Ada');
    say('one');

    say(label('finish'));

    expect(formStateNow()->answers)->toBe(['name' => 'Ada', 'first' => 'one', 'second' => null, 'third' => null]);
});

it('does not offer Finish while a required step is still open, even past the optional one', function () {
    startSample();
    expect(keyLabels())->not->toContain(label('finish'));

    say('SUMMER');
    say('Percentage');
    say('20');

    // max_discount is optional but the category after it is required.
    expect(formStateNow()->step)->toBe('max_discount')
        ->and(keyLabels())->toContain(label('skip'))
        ->and(keyLabels())->not->toContain(label('finish'));
});

it('offers Finish again when going back through a completed form', function () {
    fillSample();

    say(label('back'));   // category

    expect(keyLabels())->toContain(label('finish'));

    say(label('finish'));

    expect(formStateNow()->step)->toBe(FormState::CONFIRM)
        ->and(formStateNow()->answers['category'])->toBe('c3');
});

it('asks a skippable step, and a skip stores null', function () {
    startSample();
    say('SUMMER');
    say('Percentage');
    say('20');

    expect(formStateNow()->step)->toBe('max_discount')
        ->and(keyLabels())->toContain(label('skip'));

    say(label('skip'));

    expect(formStateNow()->answers['max_discount'])->toBeNull()
        ->and(formStateNow()->step)->toBe('category');
});

it('goes back, offers Next on an answered step and goes forward without retyping', function () {
    startSample();
    say('SUMMER');
    say('Percentage');

    say(label('back'));

    expect(formStateNow()->step)->toBe('type')
        ->and(keyLabels())->toContain(label('back'), label('next'))
        ->and(sentTexts())->toContain('Current')->toContain('Percentage');

    say(label('next'));

    expect(formStateNow()->step)->toBe('amount')
        ->and(formStateNow()->answers)->toBe(['code' => 'SUMMER', 'type' => 'percentage']);
});

it('never offers Next past the first unanswered step', function () {
    startSample();
    say('SUMMER');

    expect(keyLabels())->not->toContain(label('next'));
});

it('goes back by asking the earlier question again, leaving what was said as it is', function () {
    startSample();
    say('SUMMER');
    say('Percentage');

    say(label('back'));

    expect(tgCalls('sendMessage')->last()['text'])->toContain('Type')->toContain('Current')
        ->and(tgCalls('editMessageText')->all())->toBe([]);
});

it('clears an answer that depends on a changed one and says so', function () {
    fillSample();
    expect(formStateNow()->step)->toBe(FormState::CONFIRM);

    say(label('back'));   // category
    say(label('back'));   // max_discount
    say(label('back'));   // amount
    say(label('back'));   // type
    say('Fixed amount');

    expect(formStateNow()->answers)->not->toHaveKey('amount')
        ->and(formStateNow()->answers['type'])->toBe('fixed')
        ->and(formStateNow()->step)->toBe('amount')
        ->and(sentTexts())->toContain('Amount')->toContain('Type');
});

it('keeps an answer that depends on nothing that changed, and Next walks through it', function () {
    fillSample();

    say(label('back'));   // category
    say(label('back'));   // max_discount
    say(label('back'));   // amount
    say(label('back'));   // type
    say('Percentage');    // same value: nothing is cleared

    expect(formStateNow()->answers['amount'])->toBe('20')
        ->and(formStateNow()->step)->toBe('amount');
});

it('restores an answer hidden by a changed condition when the condition holds again', function () {
    fillSample();
    expect(formStateNow()->answers['max_discount'])->toBe('50');

    say(label('back'));   // category
    say(label('back'));   // max_discount
    say(label('back'));   // amount
    say(label('back'));   // type
    say('Fixed amount');
    say('5');             // new amount; max_discount is now hidden

    expect(formStateNow()->step)->toBe('category');

    say(label('back'));   // amount
    say(label('back'));   // type
    say('Percentage');    // amount is cleared, max_discount applies again
    say('30');

    // max_discount was never deleted, only hidden, so it is back.
    expect(formStateNow()->answers['max_discount'])->toBe('50')
        ->and(formStateNow()->step)->toBe('max_discount');
});

it('builds a prompt from the earlier answers when the step asks for that', function () {
    startSample();
    say('SUMMER');
    say('Percentage');

    expect(sentTexts())->toContain('How much? (percentage)');

    say('20');

    expect(tgCalls('sendMessage')->slice(-2)->first()['text'])->toContain('Amount')->toContain('20');
});

it('rejects a new answer that breaks the rules of the current step', function () {
    startSample();
    say('SUMMER');
    say('Percentage');
    say('20');
    say(label('skip'));

    say(label('back'));   // max_discount
    say(label('back'));   // amount
    say('500');           // not a valid percentage

    expect(formStateNow()->step)->toBe('amount')
        ->and(formStateNow()->answers['amount'])->toBe('20');
});

it('clears a later answer whose rules stop passing after an earlier change, even without a declared dependency', function () {
    test()->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('X#y', peerId: FORM_PEER))->assertOk();
    SafetyNetForm::start();
    say('Fixed amount');
    say('500');           // fine as a fixed amount

    expect(formStateNow()->step)->toBe(FormState::CONFIRM);

    say(label('back'));   // cap
    say(label('back'));   // type
    say('Percentage');    // 500 is no longer a valid cap

    expect(formStateNow()->answers)->toBe(['type' => 'percentage'])
        ->and(formStateNow()->step)->toBe('cap')
        ->and(sentTexts())->toContain('Cap');
});

it('shows the summary once everything is answered and completes on Confirm', function () {
    fillSample();

    expect(formStateNow()->step)->toBe(FormState::CONFIRM)
        ->and(keyLabels())->toContain(label('confirm'), label('back'))
        ->and(sentTexts())->toContain('SUMMER')->toContain('Category 3');

    say(label('confirm'));

    [$answers, $ctx] = SampleForm::$completed;
    expect($answers)->toBe(['code' => 'SUMMER', 'type' => 'percentage', 'amount' => 20.0, 'max_discount' => '50', 'category' => 'c3'])
        ->and($ctx)->toBe(['lastPage' => 2])
        ->and($this->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state)->toBeNull()
        ->and(sentTexts())->toContain('Created')
        ->and(tgCalls('editMessageText')->pluck('text')->all())->toBe([__('tbe::forms.prompt.picked'), 'Refreshed list']);
});

it('completes with only the applicable answers', function () {
    startSample();
    say('SUMMER');
    say('Fixed amount');
    say('5');
    test()->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('FORM#pick?3&c1', peerId: FORM_PEER))->assertOk();
    say(label('confirm'));

    expect(SampleForm::$completed[0])->toBe(['code' => 'SUMMER', 'type' => 'fixed', 'amount' => 5.0, 'category' => 'c1']);
});

it('cancels: gives the starting message back and runs onCancel', function () {
    startSample();
    say('SUMMER');

    say(__('tbe::cancel_process.reply_key'));

    expect($this->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state)->toBeNull()
        ->and(SampleForm::$cancelled)->toBe([['lastPage' => 2]]);
});

it('expires a form left idle, cancelling it', function () {
    startSample();
    $user = $this->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole();
    $state = formStateNow();
    $state->at = time() - 90000;
    $user->changeState($state->toStateString());

    say('SUMMER');

    expect($this->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state)->toBeNull()
        ->and(tgCalls('sendMessage')->last()['text'])->toBe(__('tbe::forms.expired'))
        ->and(SampleForm::$cancelled)->toBe([['lastPage' => 2]]);
});

it('offers a dynamic choice as paged inline buttons and takes a tap as the answer', function () {
    startSample();
    say('SUMMER');
    say('Percentage');
    say('20');
    say(label('skip'));

    $options = tgCalls('sendMessage')->map(fn ($call) => tgMarkup($call))->last(fn ($markup) => isset($markup['inline_keyboard']));
    $buttons = collect($options['inline_keyboard'])->flatten(1);

    expect($buttons->pluck('text')->all())->toContain('Category 1', 'Category 8', '1/2')
        ->and($buttons->pluck('text')->all())->not->toContain('Category 9');

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('FORM#page?4&2', peerId: FORM_PEER))->assertOk();
    $paged = collect(tgMarkup(tgCalls('editMessageText')->last())['inline_keyboard'])->flatten(1)->pluck('text')->all();
    expect($paged)->toContain('Category 12', '2/2');

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('FORM#pick?4&c12', peerId: FORM_PEER))->assertOk();
    expect(formStateNow()->answers['category'])->toBe('c12')
        ->and(formStateNow()->step)->toBe(FormState::CONFIRM);
});

it('ignores a tap on a stale option button', function () {
    startSample();
    say('SUMMER');

    // Step 4 is not the open step (that is "type").
    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('FORM#pick?4&c2', peerId: FORM_PEER))->assertOk();

    expect(formStateNow()->answers)->toBe(['code' => 'SUMMER'])
        ->and(tgCalls('answerCallbackQuery')->last()['text'])->toBe(__('tbe::forms.errors.outdated'));
});

it('ignores a tap on an option that is not offered', function () {
    fillSampleUntilCategory();

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('FORM#pick?4&nope', peerId: FORM_PEER))->assertOk();

    expect(formStateNow()->step)->toBe('category');
});

function fillSampleUntilCategory(): void
{
    startSample();
    say('SUMMER');
    say('Percentage');
    say('20');
    say(label('skip'));
}

it('keeps the whole state small however long the transcript grows', function () {
    fillSample();

    expect(strlen(test()->bot->botUsers()->where('telegram_user_peer_id', FORM_PEER)->sole()->state))->toBeLessThan(600);
});
