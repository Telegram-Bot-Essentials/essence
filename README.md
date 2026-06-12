# Telegram Bot Essentials (Essence)

A Laravel package for building multi-tenant Telegram bots with a structured request-routing architecture. Instead of one giant update handler, incoming webhook updates are dispatched through typed buses: **Commands**, **ReplyKeys**, **CallbackQueries**, and **StateAnswers**.

## Requirements

- PHP ^8.2
- Laravel ^12 or ^13
- [irazasyed/telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) ^3.15
- [stancl/tenancy](https://tenancyforlaravel.com/) ^3.9 (each bot is a tenant)

## Installation

```bash
composer require telegram-bot-essentials/essence
```

Publish config and translations:

```bash
php artisan tbe:install
php artisan migrate
```

Initialize your main bot and set the webhook:

```bash
php artisan tbe:singlebot:init
php artisan tbe:set-webhook
```

Configure `config/tbe-essence.php` and `.env` values (`MAIN_TELEGRAM_BOT_TOKEN`, `MAIN_UNIQUE_ID`, `MAIN_ADMIN_CHAT_ID`, etc.) before running init.

## Webhook URL

Each bot receives updates at:

```
POST /api/{bot_unique_id}/telegram/bot/webhook
```

Authentication uses the Telegram `secret_token` header, verified against the hashed token stored on the `bots` table when you run `tbe:set-webhook`.

## Core Concepts

| Concept | Trigger | Purpose |
|---------|---------|---------|
| **Commands** | `/start`, `/help`, … | Slash-command handlers |
| **ReplyKeys** | Keyboard button text | Main menu and navigation |
| **CallbackQueries** | Inline button presses | Menus, actions, starting input flows |
| **StateAnswers** | Text/media while user has active state | Multi-step input, form wizards |
| **Features** | Called from queries/answers | Build `TelegramResponse` messages |
| **MessageMeta** | Associated with messages | Lock, revert, and update bot messages |
| **StateData** | Referenced by ID | JSON payload storage for wizards and deeplinks |

## Project Layout (in your Laravel app)

```
app/Telegram/
├── CallbackQueries/
│   ├── Admin/
│   └── Member/
├── StateAnswers/
│   ├── Admin/
│   └── Member/
├── ReplyKeys/
│   ├── Admin/
│   └── Member/
├── Features/
│   ├── Admin/
│   └── Member/
└── Commands/
```

The webhook controller auto-loads classes from `app/Telegram/CallbackQueries/*` and `app/Telegram/StateAnswers/*` on each request. Package defaults are loaded from `vendor/telegram-bot-essentials/essence/src/Telegram/`.

## Documentation

- [Architecture](docs/architecture.md) — webhook flow, buses, encoding formats
- [State & StateData](docs/state-and-statedata.md) — `bot_users.state` vs `state_data` table
- [Extending](docs/extending.md) — building CallbackQueries, StateAnswers, Features
- [Reference](docs/reference.md) — helpers, config, database, artisan commands

## Artisan Generators

```bash
php artisan make:callback-query Admin/MyFeatureQuery
php artisan make:state-answer Member/MyFeatureAnswer
php artisan make:reply-key Member/MyFeatureKey
php artisan make:feature Admin/MyFeatureFeature
php artisan make:command MyStartCommand
```

## Related Packages

Essence is the core framework. Optional companion packages extend it:

- `telegram-bot-essentials/billing` — invoices and payments
- `telegram-bot-essentials/settings` — bot settings
- `telegram-bot-essentials/user-wallet` — user wallet/credits

Register their CallbackQueries and StateAnswers in each package's service provider via `callbackQueryBus()` and `stateAnswerBus()`.

## License

GPL-3.0
