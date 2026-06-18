# Events & Listeners

The package fires lifecycle events at every stage of update processing. You can listen to these events to add cross-cutting behaviour — usage history, analytics, affiliate tracking, notifications — without touching handler classes.

## Quick Start

Register listeners in your `AppServiceProvider::boot()`:

```php
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Events\BotCallbackQueryHandled;

public function boot(): void
{
    botEventBus()->listen(BotDeepLinkReceived::class, AffiliateListener::class);
    botEventBus()->listen(BotCallbackQueryHandled::class, UserActivityListener::class);
}
```

Write a listener as a plain invokable class:

```php
class AffiliateListener
{
    public function handle(BotDeepLinkReceived $event): void
    {
        $user    = $event->context->botUser;   // BotUser model
        $payload = $event->payload;            // e.g. "ref_abc123"

        Referral::record($user, $payload);
    }
}
```

## The `botEventBus()` Helper

`botEventBus()` returns the `BotEventBus` singleton. It has two methods:

| Method | What it does |
|--------|-------------|
| `listen(string $event, $listener)` | Register a listener for a TBE event |
| `fire(BotEvent $event)` | Dispatch an event (used internally by the package) |

`listen()` is chainable:

```php
botEventBus()
    ->listen(BotUpdateReceived::class, LogAllUpdates::class)
    ->listen(BotUpdateUnhandled::class, AlertOnUnhandled::class);
```

## Built-in Events

All events extend `BotEvent`, which carries a `WebhookContext $context` snapshot.

### `BotUpdateReceived`

Fires for every incoming update **before** routing begins. Useful for logging or rate-limiting.

```
namespace TelegramBotEssentials\Essence\Events

Properties:
  WebhookContext $context
  string         $updateType   // 'message', 'callback_query', etc.
```

```php
botEventBus()->listen(BotUpdateReceived::class, function (BotUpdateReceived $event) {
    Log::info('update received', [
        'type'    => $event->updateType,
        'user_id' => $event->context->botUserId,
    ]);
});
```

---

### `BotCommandHandled`

Fires after a `/command` is dispatched, whether or not the command existed.

```
Properties:
  WebhookContext $context
  string         $command   // e.g. '/start', '/help'
```

---

### `BotDeepLinkReceived`

Fires when the user sends `/start <payload>` — i.e. followed a deep link. Fires **in addition to** `BotCommandHandled`, not instead of it.

```
Properties:
  WebhookContext $context
  string         $payload   // the text after '/start '
```

**Affiliate system example:**

```php
botEventBus()->listen(BotDeepLinkReceived::class, function (BotDeepLinkReceived $event) {
    [$type, $code] = explode('_', $event->payload, 2) + [null, null];

    if ($type === 'ref') {
        Referral::create([
            'referrer_code' => $code,
            'new_user_id'   => $event->context->botUserId,
        ]);
    }
});
```

---

### `BotReplyKeyHandled`

Fires after a keyboard button is matched and its handler runs.

```
Properties:
  WebhookContext $context
  string         $keyText   // the button text that was pressed
```

---

### `BotStateAnswerHandled`

Fires after a state answer handler runs. `$state` is the state that was active **at the start of the request** (before the handler may have cleared it).

```
Properties:
  WebhookContext $context
  string         $state   // encoded state string, e.g. 'PRODUCTS#add?target_field=title'
```

---

### `BotCallbackQueryHandled`

Fires after a callback query handler runs successfully.

```
Properties:
  WebhookContext $context
  string         $type     // callback type, e.g. 'BUYSVC'
  string         $method   // callback method, e.g. 'pay'
```

**Usage history example:**

```php
botEventBus()->listen(BotCallbackQueryHandled::class, function (BotCallbackQueryHandled $event) {
    UserActivity::log(
        userId: $event->context->botUserId,
        action: $event->type . '#' . $event->method,
    );
});
```

---

### `BotUpdateUnhandled`

Fires when an incoming message does not match any command, reply key, or state answer. The "request is invalid" message to the user is sent **after** this event.

```
Properties:
  WebhookContext $context
```

---

## The `WebhookContext` on Events

Every event carries a `WebhookContext` snapshot captured before routing starts. It gives you read access to the full request context:

| Property | Type | Description |
|----------|------|-------------|
| `$context->botUser` | `BotUser` | The user who sent the update |
| `$context->bot` | `Bot` | The bot (tenant) that received it |
| `$context->botUserId` | `int` | BotUser primary key |
| `$context->botId` | `int` | Bot primary key |
| `$context->botToken` | `string` | Bot API token |
| `$context->updatePayload` | `array` | Raw Telegram update as array |

For a Telegram `Api` instance, call `$event->context->api()`.

---

## Queued Listeners

`WebhookContext` is fully serializable, so queued listeners work without extra setup. Implement Laravel's `ShouldQueue` interface:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use TelegramBotEssentials\Essence\Events\BotCallbackQueryHandled;

class UserActivityListener implements ShouldQueue
{
    public string $queue = 'low';

    public function handle(BotCallbackQueryHandled $event): void
    {
        UserActivity::log(
            userId: $event->context->botUserId,
            action: $event->type . '#' . $event->method,
        );
    }
}
```

Register as usual:

```php
botEventBus()->listen(BotCallbackQueryHandled::class, UserActivityListener::class);
```

If you need to call the Telegram API from a queued listener, restore the webhook context first:

```php
public function handle(BotDeepLinkReceived $event): void
{
    $event->context->apply();  // restores wHook() state

    wHook()->api()->sendMessage([
        'chat_id' => wHook()->user()->telegramUser->peer_id,
        'text'    => 'Welcome via referral!',
    ]);
}
```

---

## Firing Custom Events from a Package or Module

If you are building a companion package (billing, affiliate, etc.) and want to fire your own events that carry the webhook context, extend `BotEvent`:

```php
use TelegramBotEssentials\Essence\Events\BotEvent;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class ServicePurchased extends BotEvent
{
    public function __construct(
        WebhookContext $context,
        public readonly VServiceOrder $order,
    ) {
        parent::__construct($context);
    }
}
```

Fire it from a CallbackQuery or StateAnswer:

```php
botEventBus()->fire(new ServicePurchased(
    context: WebhookContext::capture(),
    order:   $order,
));
```

Listen in any service provider:

```php
botEventBus()->listen(ServicePurchased::class, ProvisionServiceListener::class);
```

---

## Event Firing Order

For a **message** update:

```
BotUpdateReceived
  └── /command          → BotCommandHandled
                           └── /start <payload>  → BotDeepLinkReceived
  └── keyboard text     → BotReplyKeyHandled
  └── text (has state)  → BotStateAnswerHandled
  └── nothing matched   → BotUpdateUnhandled
```

For a **callback_query** update:

```
BotUpdateReceived
  └── handled           → BotCallbackQueryHandled
```
