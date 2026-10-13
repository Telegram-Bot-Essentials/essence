# Plan: multi-chat context (groups, supergroups, forum topics)

Status: **in progress** — Phase 0 shipped in 0.17.1; Phase 1 (conversations) in 0.18.0;
decisions settled 2026-10-12 (see bottom).

## Problem

Essence was built assuming every update comes from the user's private chat with the bot:

- State lives on the user: `bot_users.state` (`BotUser::changeState()` / `addParamToState()`).
  `TelegramBotAuthentication::fetchUserData()` keys only on `from.id`, so a group message runs
  against the user's private-chat state.
- Replies target the user, not the chat: ~113 sends to `telegramUser->peer_id` and ~53 to
  `wHook()->peerId()` (which resolves to `from.id`) across essence + companions. Nothing reads
  `chat.id` / `chat.type` for routing.
- Group `my_chat_member` updates are already rejected (`isPrivateChatMemberUpdate()`), but
  `message` / `callback_query` from groups are not.

Concrete bugs today when the bot is in a group:

1. `/anything@bot` in a group runs `stateAnswerBus()->cancelHandler()` + `cancelOldProcess()` —
   it kills the user's half-finished flow in their DM (e.g. a purchase form).
2. Group text (privacy mode off, or a reply to the bot) can be consumed by
   `processStateAnswers()` as the next answer of the DM form.
3. Unrecognised group text makes the bot DM the user `requestIsInvalid`.
4. Every reply lands in the user's DM instead of the group.

## Key insight

In a private chat, `chat.id == from.id`. So the existing per-user state maps **exactly** onto
"this user's conversation in their private chat". That makes almost the whole redesign
shippable non-breaking, without touching the 166 send sites up front.

## Model

Split **who** (`BotUser`) from **where** (chat):

- `BotUser` — identity, balance, roles. Unchanged.
- `bot_chats` (new) — `chat_id`, `type` (private/group/supergroup/channel), `title`,
  `username`, `bot_status` (member/administrator/left/kicked).
- `conversations` (new) — state keyed by `(bot_user_id, chat_id, thread_id)`.
  Migration copies each `bot_users.state` into a row with `chat_id = peer_id`, `thread_id = null`.
  It also takes over `bot_users.menu` (the reply menu a user is on belongs to the chat it is
  shown in): copy it across and drop the column in the same migration.

## Backward-compatibility strategy

1. **Keep `$user->state`, `changeState()`, `addParamToState()` as shims** bound to the
   *current conversation*. In a private chat they behave exactly as today, so the ~30 state
   call sites in companions do not change.
2. **Handler chat scope** — every handler (command, reply key, callback query, state answer,
   text matcher, inline query where relevant) declares `Private | Group | Any`,
   **default `Private`**. Buses filter on it. Existing handlers therefore never run in groups,
   so their `peer_id` sends stay correct (in private, peer_id *is* the current chat).
3. **Send sites migrate only when a handler opts into groups.**

## Phases (one release each)

### Phase 0 — guard patch (0.17.1), shipped

Drop non-private `message` / `callback_query` updates in `TelegramBotAuthentication`, **before**
`fetchUserData()` (so no `BotUser` is minted and `markActive` doesn't fire for group lurkers).

- Reject only when a chat is present **and** `type !== 'private'`.
- Must keep: `inline_query` (no `chat`), and callbacks from inline-mode messages
  (`inline_message_id`, no `message`) — otherwise `CanResolveInlineQuery` breaks.
- Tests: group message, group callback, inline query, inline-message callback.

Fixes all four bugs above in production. Superseded later by default-`Private` scope.

### Phase 1 — chat context + per-conversation state (0.18, breaking)

- `wHook()->chat()`, `chatId()`, `threadId()`, `conversation()`, filled from `message.chat` /
  `callbackQuery.message.chat`; null for chatless inline traffic.
- `WebhookContext` carries `chat_id` + `thread_id` (it is serialized into queued jobs).
  Payloads queued before the deploy lack them → fall back to `peer_id`.
- `Webhook::runForUser()` and admin flows that mutate *another* user's state (e.g.
  `user-management` `BotUsersAnswer`) must switch to the **target's private conversation**,
  or the shim writes state into whatever group the admin is in.
- `markActive` only on private-chat updates — presence in a group doesn't prove the user
  hasn't blocked the bot in DM.
- Shipped in 0.18 as the storage and API move only: `conversations` (no `bot_chats` yet),
  `wHook()->conversation()`, `WebhookContext` chat/thread. The items below that only matter
  once group updates get through (private-only `markActive`, `has_private_chat`) move to
  Phase 2, where they can be tested.
- `has_private_chat` flag on `BotUser`. Announcements / campaigns broadcasts filter on it, and
  `BotUserStatus` must not mark group-only users blocked on "bot can't initiate conversation".
  Cross-package change — coordinate the release wave.
- Migrations run per tenant (db-per-tenant). Integration-test the suite with `composer link`.

### Phase 2 — handler scopes + group mechanics

- Bus filtering by chat scope goes live.
- Commands: parse `/cmd@botname`; ignore commands addressed to other bots.
- Callback ownership: in a group anyone can press any button. Bind the owner on `MessageMeta`
  (not in callback data — 64-byte limit, `CallbackDataTooLong`) and reject foreign clicks with
  an `answerCallbackQuery` alert.
- Keyboards in groups (decided from the prototype, see **Group keyboard findings**). No
  reply keyboard is ever sent to a group, and no call site changes: `replyKeyboard()`
  decides from the conversation.
  - No state: a markup with no buttons, which leaves keyboards on screen alone. Never
    `remove_keyboard`: in a group it hides other users' keyboards.
  - Mid-flow with a state answer keyboard: the same buttons as an **inline** keyboard on
    the prompt. A press carries the button index and a short hash of the state; it is
    replayed as if the presser typed the button text, against the **presser's**
    conversation. A presser who is not at that step (not the flow's owner, or a stale
    step) gets an `answerCallbackQuery` alert. Used buttons are taken off the message.
  - Mid-flow without one: an inline Cancel button.
  - Free-text steps: `force_reply` + `selective`, so the answer reaches the bot under
    privacy mode. The answer is routed by sender (per-user conversation), not by which
    bot message it replies to; in a group a text answer counts only when it is a reply to
    the bot.
- Forum topics: echo `message_thread_id` on every send in context.
- `migrate_to_chat_id` (group → supergroup) rewrites `bot_chats` + `conversations`.
- Anonymous admins (`GroupAnonymousBot`) and `sender_chat` messages have no real `from`:
  treat as user-less, or ignore, explicitly.
- Group `my_chat_member` → `BotChatJoined` / `BotChatLeft` events + `bot_chats.bot_status`.
- Testing helpers: chat type on `makeMessageUpdate` / `makeCallbackQueryUpdate`, plus group
  variants.
- Docs: privacy mode (on = bot only sees commands, mentions, replies).

### Phase 3 — split "reply here" from "message the user privately"

- `TelegramResponse::send()` defaults to the current chat (+ thread); explicit `->toUser()`
  for DM-only messages.
- Migrate essence internals first: `FormEngine`, `ExceptionHandler`, `CanCancelOldProcess`,
  `StateAnswer`. Companion handlers migrate only when they opt into groups.
- Payments, wallet, invoices, admin panels stay DM-only by design.

### Phase 4 — drop the shims (0.18 / 1.0)

After all companions have migrated off `BotUser::state` / `changeState()`.

## Group keyboard findings (2026-10-13)

Live test in a supergroup, privacy mode on, bot a plain member, two accounts. Prototype
kept on `proto/group-keyboards` in tg-essential-downloader-bot.

| Behaviour | Result |
|---|---|
| Reply keyboard press | Arrives as a reply to the keyboard message, so privacy mode lets it through. Anyone can press a non-selective one. |
| `selective` + reply | Only the target sees it; the press arrives. |
| Two users, each a selective keyboard | Not possible: a client shows only the newest reply markup message in the group. A keyboard, a `force_reply` or a selective remove for one user hides everyone else's. |
| Selective `remove_keyboard` | Clears the target, and as the newest markup also hides other users' keyboards. |
| Markup with no buttons | Accepted; keyboards on screen stay. |
| Inline buttons | Presses arrive whatever the privacy mode; anyone can press. |
| `force_reply` | The answer arrives as a reply to the bot. With two users at once, the older prompt's user replied to an older bot message, not their own prompt. |
| Plain group message, not a reply | Not delivered (privacy mode). |
| `message_thread_id` | Present on plain replies in a non-forum group (the reply chain); only `is_topic_message` means a forum topic. |

## Decisions (2026-10-12)

1. **Breaking, no shims.** Like the 0.17 permissions wave: `bot_users.state`,
   `changeState()` and `addParamToState()` go away in Phase 1 and state lives on the
   conversation. Essence, every companion and vshop convert in the same release wave. This
   replaces the backward-compatibility strategy above and folds Phase 4 into Phase 1.
2. **State is keyed by user × chat × topic** (`bot_user_id, chat_id, thread_id`). No
   chat-shared state for now.
3. **Errors in a group reply in the group** (and topic) where they happened, since a DM fails
   for users who never started the bot.
4. **First real group feature** — still open; it becomes the Phase 2 acceptance test.

## Related

- Keyboard menus & permissions rework (to be vetted next) — reply keyboard assumptions
  interact with Phase 2 (no reply keyboards in groups) and handler scope.
