# Reference

## Helpers

All helpers are defined in `src/helpers.php` and available globally when the package is installed.

### Context & Buses

| Helper | Returns | Description |
|--------|---------|-------------|
| `wHook()` | `Webhook` | Current request context (api, update, bot, user) |
| `telegramApi($token, $baseUrl?)` | `Api` | Create a Telegram API client |
| `replyKeyBus()` | `ReplyKeyBus` | Reply keyboard handler registry |
| `callbackQueryBus()` | `CallbackQueryBus` | Inline callback handler registry |
| `stateAnswerBus()` | `StateAnswerBus` | Text input handler registry |
| `stateData()` | `StateDataService` | StateData create/merge service |
| `exceptionHandler()` | `ExceptionHandler` | Webhook exception handler |

### Encoding

| Helper | Description |
|--------|-------------|
| `encodeCallback($type, $method, $params = [])` | Encode inline button callback_data (64-byte limit) |
| `decodeCallback($input)` | Decode callback_data → `['type', 'method', 'params']` |
| `encodeAnswerState($type, $method, $params = [])` | Encode user state string |
| `decodeAnswerState($input)` | Decode user state → `['type', 'method', 'params']` |

### Registration

| Helper | Description |
|--------|-------------|
| `loadCallbackQueries($path)` | Scan directory and register CallbackQuery subclasses |
| `loadStateAnswers($path)` | Scan directory and register StateAnswer subclasses |
| `loadReplyKeys($path)` | Scan directory and register ReplyKey subclasses |
| `addUserReplyKeys(array $rows)` | Register reply keys from config keyboard array |
| `resolveNamespace($path)` | Resolve PSR-4 namespace from filesystem path |

### UI Helpers

| Helper | Description |
|--------|-------------|
| `inlineSorter($buttons, $step?)` | Arrange inline buttons into rows |
| `smartInlineKeysSorter($buttons, $step?, $limit?)` | Sort by text length with row width limit |
| `addInlineKeysSorted($keyboard, $keys, $step?)` | Add sorted rows to a Keyboard |
| `addInlineKeysSmartSorted($keyboard, $keys, $step?, $limit?)` | Smart-sorted rows |
| `getInputInlineKeyText()` | Get text of the pressed inline button |
| `inlineConfirmationKey($text, $target, $back, $confirmText?)` | Create a confirmation button |

### Access & Guards

| Helper | Description |
|--------|-------------|
| `hasAccess(?int $power)` | Check user role against required power level |
| `dependsOn(?bool $condition, ?string $message)` | Throw `FeatureIsDisabled` if condition is falsy |

### Debugging

| Helper | Description |
|--------|-------------|
| `exceptionReport(Throwable $e)` | Log + send trace/update to bug report chat |
| `debugMessage(string $message)` | Log + send debug text to bug report chat |
| `mixedDebugMessage(mixed $data)` | Log + send JSON-encoded data |

## Configuration

Published to `config/tbe-essence.php` via `php artisan tbe:install`.

```php
return [
    // Reply keyboard layout per role
    'keyboard' => [
        'admin'  => [[AdminPanelKey::class], ...],
        'member' => [[MainMenuKey::class], ...],
    ],

    // Telegram slash commands
    'commands' => [
        HelpCommand::class,
    ],

    // Bot management API token
    'bot_access' => [
        'token' => env('BOT_MANAGEMENT_ACCESS_TOKEN'),
    ],

    // Single-bot mode defaults
    'main' => [
        'unique_id'          => env('MAIN_UNIQUE_ID', 'main'),
        'telegram_bot_token' => env('MAIN_TELEGRAM_BOT_TOKEN'),
        'admin_chat_id'      => env('MAIN_ADMIN_CHAT_ID'),
        'currency'           => env('MAIN_CURRENCY', 'USD'),
    ],

    // Telegram API base URL
    'base_bot_url' => env('TELEGRAM_BOT_BASE_URL', 'https://api.telegram.org/bot'),

    // Error reporting destination
    'bug_report' => [
        'telegram_chat_id' => env('BUG_REPORT_TELEGRAM_CHAT_ID'),
    ],

    // Developer override for hasAccess()
    'developer' => [
        'peer_id' => env('DEVELOPER_PEER_ID'),
    ],
];
```

### telegram.php

Merged from the package but typically overridden by the host app. Configures the telegram-bot-sdk `BotsManager` and HTTP client handler (`LaravelHttpClient`).

## Database

### Tables

| Table | Model | Description |
|-------|-------|-------------|
| `bots` | `Bot` | Bot tenant (token, owner, activation) |
| `telegram_users` | `TelegramUser` | Global Telegram user profiles |
| `bot_users` | `BotUser` | Per-bot user (power, state, menu) |
| `message_metas` | `MessageMeta` | Tracked messages for lock/revert |
| `state_data` | `StateData` | JSON payload storage |
| `inline_confirmations` | `InlineConfirmation` | Confirmation dialog state |

### bot_users columns

| Column | Type | Description |
|--------|------|-------------|
| `bot_id` | FK | Tenant bot |
| `telegram_user_peer_id` | FK | Telegram user |
| `power` | int | Role level (see `Roles` enum) |
| `state` | string, nullable | Encoded active input flow |
| `menu` | enum | `main` or `admin` — controls keyboard |
| `suspended_at` | timestamp | Suspension flag |
| `last_interaction` | timestamp | Last activity |

### state_data columns

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `data` | json | Arbitrary payload |

### BotUser methods

```php
$botUser->changeState(?string $state);     // set or clear active state
$botUser->addParamToState(array $params);  // merge params into current state
$botUser->getKeyboard();                   // build reply keyboard for current context
$botUser->interact();                      // update last_interaction timestamp
```

### StateDataService methods

```php
stateData()->store(array $data = []): StateData;
stateData()->addData(StateData $row, array $data): void;
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `tbe:install` | Publish config and translations |
| `tbe:singlebot:init` | Create bot from `tbe-essence.main` config |
| `tbe:set-webhook` | Set Telegram webhook and secret token |
| `tbe:bot-management-token` | Generate bot management API token |
| `make:callback-query {name}` | Generate CallbackQuery class |
| `make:state-answer {name}` | Generate StateAnswer class |
| `make:reply-key {name}` | Generate ReplyKey class |
| `make:feature {name}` | Generate Feature class |
| `make:command {name}` | Generate Telegram Command class |

### tbe:set-webhook options

```bash
php artisan tbe:set-webhook --unique-id=main
php artisan tbe:set-webhook --endpoint="/api/{unique_id}/telegram/bot/webhook"
```

Generates a new `secret_token`, hashes it on the bot record, and registers the webhook URL with Telegram.

## Enums

### Roles (`src/Enums/Roles.php`)

```php
Roles::ADMIN->value      // 100
Roles::MODERATOR->value  // 10
Roles::MEMBER->value     // 0
```

### AllowableFields (`src/Enums/AllowableFields.php`)

Used in StateAnswer `$allowedFields`:

```php
AllowableFields::TEXT->value      // 'text'
AllowableFields::PHOTO->value     // 'photo'
AllowableFields::DOCUMENT->value  // 'document'
```

## Routes

Defined in `routes/api.php`:

| Method | Path | Handler |
|--------|------|---------|
| GET/POST/PUT/PATCH/DELETE | `/api/bots` | Bot CRUD (management API) |
| GET | `/api/{bot}/telegram/bot/webhook` | Health check |
| POST | `/api/{bot}/telegram/bot/webhook` | Webhook handler |

Webhook POST requires header `X-Telegram-Bot-Api-Secret-Token` matching the bot's stored secret.

## TelegramResponse API

```php
$response = new TelegramResponse(
    text: 'Hello',
    replyMarkup: $keyboard,
    parseMode: 'HTML',
);

$response->answer('Done!');           // callback toast text
$response->softAnswer('Saved');       // silent callback answer
$response->photo($inputFile);         // send as photo with caption
$response->messageMetaModel($model);  // attach MessageMeta on send

$response->send($chatId);             // new message
$response->update($chatId, $messageId); // edit existing message
```

When called from a CallbackQuery handler without arguments, `update()` edits the message that contained the pressed button.

## Webhook Context Export (for Jobs)

```php
$context = wHook()->exportContext();

// In job:
wHook()->importContext($context);
wHook()->api()->sendMessage([...]);
```

Useful for sending Telegram messages from queued jobs while preserving bot/user context.
