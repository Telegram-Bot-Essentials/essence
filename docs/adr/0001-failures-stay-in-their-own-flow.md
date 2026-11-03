# Failures stay in their own flow

One blocked bot or Telegram blip used to stop work that had nothing to do with
it: a referrer's blocked chat cost a new member their bonus, a member's blocked
chat put an error on an admin's screen, and a failed `getMe()` left a verified
payment unpaid. Throwing was not the cause. There were no boundaries between
steps, so every failure reached the same catch-all whatever had already happened.

We keep exceptions and draw the boundaries. Every step of a flow is one of:

| Kind | Example | Handling |
|---|---|---|
| Atomic | Debit and payment attempt | One `DB::transaction()` |
| Required | Provisioning an order | In the request, result recorded (`Invoice::fulfill()`) |
| Reaction | Notices, admin alerts | `isolated()`, `background()` or `notifyUser()` |
| Direct reply | Answering the tap | Plain send; failure ends that request |

Ask of a step: if this fails, should what came before be undone? If so it is
atomic. If not it is a reaction.

## Consequences

- `runForUser()` only switches who the webhook acts for. Only the controller's
  boundary talks to users about failures.
- Refusing a user's request is a `HandlerGate`, checked after a handler claims the
  update. Listeners only watch, and cannot stop an update or each other.
- `background()` is one generic job carrying a closure, not a class per action.
  It runs once unless given `tries`, because repeating work is not always safe.
- Sends are not retried after a read timeout: the message may have arrived.
- `sweepTelegramFailures()` tests that a flow's promise holds whichever Telegram
  call fails, and a PHPStan rule flags Telegram calls inside transactions.

## Considered and rejected

- **Result objects instead of exceptions.** A forgotten check fails silently.
- **Storing and replaying updates for the channel lock.** Letting `/start` and deep
  links through, and gating only what users ask for, needs no state at all.
- **An outbox for every send.** Menus need instant replies and the message id back.
- **A workflow engine.** No flow here needs branching or automatic compensation.
- **Redelivering failed updates.** Handlers are not idempotent.
