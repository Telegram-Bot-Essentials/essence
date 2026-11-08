# Web accounts are the app's users

The panel first signed people in as a `BotUser`. That made a login belong to one
bot: an owner of two bots had two separate sessions, nobody could have a
password, and there was nowhere for a login that spans every bot. A `BotUser`
is a person inside one bot, so it was the wrong thing to log in as.

A web account is the app's own user model, `App\Models\User` by default, as
Cashier, Sanctum and Spatie Permission do it. Essence ships no user table. It
gives the app:

- a `TelegramAccount` contract and a `HasTelegramAccount` trait for its model;
- the `account_telegram_users` table, linking one account to many Telegram
  accounts (`telegram_user_peer_id` is unique, so a Telegram account belongs
  to at most one web account);
- `tbe-essence.web.account_model`, read when the panel is on. Turning the panel
  on without a model that implements the contract fails at boot.

```
users (app) 1 ── * account_telegram_users * ── 1 telegram_users
                                                    │ 1
                                   bots.bot_owner_peer_id, bot_users (*)
```

A bot is in the panel's list when a linked Telegram account has a `BotUser` in
it that the Gate allows `web.manage`. Owning a bot without a `BotUser` there
(never started it) is not enough: there would be no one to act as. Inside a
bot, the request acts as exactly one of those `BotUser`s, the bot's owner
first, then in link order. The list, the access check and the acting user all
come from that one lookup, so they cannot disagree.

The Gate is always asked with that `BotUser`, never with the account: ability
rules (tbe-access's `RoleGate` among them) only answer for `BotUser`s.

The panel shows which account it acts as. `BindWebContext` hands that `BotUser`
to `wHook()`, so the Gate, handler gates and companion screens see a `BotUser`
as before and need no changes.

## Signing in and linking

- `/panel` sends a one-time link that signs in the web account linked to that
  Telegram account. For an unlinked one it creates a web account with no
  password, after warning that signing in first and linking would avoid a
  second account.
- More Telegram accounts are linked from a signed-in browser: a deep link to
  the bot with a one-time code, confirmed from the Telegram account being added.
  Starting from the browser makes the target account unambiguous.
- Passwords, "remember me" and the login page are the framework's, on the
  app's model. A Telegram account can be unlinked, but not the last one of an
  account without a password.

## Consequences

- `WebUser` and `PanelUser` go away. `BotUser` is never authenticated.
- Every panel request looks up its `BotUser` from the account's links, one
  indexed query that is kept for the request.
- Screens that are not about one bot (an app's own platform screens) live in
  the app, in its own panel, on its own guard if it likes. Essence's panel
  stays per bot.
- Essence depends on a contract the app's model implements, not on the model.
- The app's model may serve other Filament panels too, so essence's trait does
  not implement `canAccessPanel()`, `getTenants()` or `canAccessTenant()`. It
  gives helpers the app's own methods call for essence's panel id.

## Considered and rejected

- **Passwords on `bot_users`.** One password per bot, credentials beside every
  customer row, and no place for a login across bots.
- **`telegram_users` as the account.** Credentials in a mirror of Telegram data,
  and no account possible without Telegram.
- **A column on `users` for the Telegram account.** One Telegram account per
  web account, when a person may use several.
- **One entry per bot and Telegram account in the bot list.** Honest, but
  noisy; switching hats inside one bot is rare enough for the order above.
- **Merging web accounts.** Unlinking from one and linking to the other covers it.
