# Plan: replaceable keyboards and access control

Status: **in progress**, all uncommitted:
- essence on `feat/keyboards-and-access` (0.17, breaking: `power`, `Roles` and `$perm` removed);
- `tbe-access` package at `~/MyProjects/tbe-access`;
- user-management and every other companion converted to permission names;
- vshop: handlers converted, and an app migration moves power admins to a "Full admin" role.

## Problem

### Keyboards

- Only two menus can exist: `bot_users.menu` is `enum('main','admin')`, and
  `BotUser::getKeyboard()` hardcodes `main` / `admin` branches reading
  `tbe-essence.keyboard.member` / `.admin`. A third menu needs a migration and an essence edit.
- UI lives on the model: `getKeyboard()` / `keyboardGenerator()` sit on `BotUser`, with no
  contract an app can rebind.
- A reply keyboard is always sent (~132 `'reply_markup' => wHook()->user()->getKeyboard()`
  sites). Emptying the config still sends an empty `ReplyKeyboardMarkup`, which does not
  remove a keyboard already on screen. A bot that wants inline-only UI cannot opt out.
- `one_time_keyboard` is hardcoded.

### Permissions

- One ladder: `bot_users.power` checked as `power >= N` (`hasAccess()`, `Roles` enum
  100/10/0). Higher always inherits lower, so two separate jobs ("invoices" vs "panels")
  cannot exist.
- In practice binary: every handler is `$perm` 0 or 100; MODERATOR is unused.
- Packages cannot declare their own permissions.
- Owner / developer bypass hardcoded in the helper.
- Admin management is duplicated: essence `BotAdminsFeature` (sets power 100/0) and
  user-management `BotUsersQuery::changeRole` (cycles `Roles`).
- Good part to keep: every bus already funnels through one `hasAccess($handler->getPerm())`.

## Principle

Essence ships a **default you can turn off or replace** — same pattern as
`ResolvesBotLocale` / `DefaultBotLocaleResolver`: a contract in `src/Contracts`, a default in
`src/Support`, `bind()`-ed in `TelegramBotServiceProvider`, rebound by a companion that owns the
real behaviour. Essence never names the companion.

Not every bot needs a reply keyboard; not every bot needs roles.

## Keyboards

- `Contracts\ResolvesKeyboard::for(BotUser $user): Keyboard`, default
  `Support\DefaultKeyboardResolver` (the logic moved off `BotUser`).
- `BotUser::getKeyboard()` is replaced by a `replyKeyboard(?BotUser $user = null)` helper that
  reads the webhook context, so multi-chat can make keyboards chat-aware without another
  sweep over the call sites.
- `bot_users.menu` stays for now and moves into per-chat state with multi-chat (see
  `multi-chat-context.md`), so it moves once.
- Named menus: `tbe-essence.keyboard.menus => ['main' => [...rows], 'admin' => [...rows]]`.
- `bot_users.menu` becomes a plain string column (default `main`), so any menu name works.
- `tbe-essence.keyboard.enabled` (default `true`). When `false`, the default resolver returns
  `ReplyKeyboardRemove` instead of a menu, so an old keyboard is cleared and none is shown.
  Explicit state-answer keyboards (`keyboardFor`) are still honoured — the handler asked for
  them; the automatic menus and the automatic Cancel row are not.
- `tbe-essence.keyboard.one_time` / `.resize` replace the hardcoded flags.
- Buttons are filtered through the access contract below.
- Multi-chat interaction (see `multi-chat-context.md`, Phase 2): "no reply keyboard in groups"
  becomes one rule inside the default resolver, not a second sweep over call sites.

## Access control — two models, one contract

| Model | For | Who decides |
|---|---|---|
| No roles | Simple bots | Handlers with no permission are public; anything that names one → owner + developer only |
| Roles | Bots with staff or admins | Opt-in package `tbe-access` rebinds the contract |

There is no built-in "admin" level: a bot that needs admins besides the owner installs
`tbe-access` and gives them a role holding `*`.

### In essence (no new tables)

- `Contracts\AuthorizesAccess::allows(BotUser $user, ?string $permission): bool`, plus
  `usersWith(Bot $bot, string $permission)` for reaching holders (admin alerts).
- Default `Support\DefaultAuthorizer`:
  1. `null` → allowed (public).
  2. Bot owner (`bots.bot_owner_peer_id`) or `tbe-essence.developer.peer_id` → allowed.
  3. Anything else → denied. `usersWith()` is empty.
- `Support\PermissionRegistry` (singleton, in-memory) — packages register
  `name`, translated label, group in their service provider. Pure data, so companions can
  declare permissions whether or not `tbe-access` is installed.
- Handlers declare `protected ?string $permission = 'billing.offers.manage'`; `null` is public.
  Base classes implement `Contracts\DeclaresPermission`.
- `hasAccess(?string $permission)` delegates to the contract.
- `bot_users.power`, the `Roles` enum, `$perm` / `getPerm()` and the bot admins screen are
  removed.
- `BotCommandMenu` publishes only commands the public tier can use.

### `tbe-access` (new package, requires essence + user-management)

- Flat, owner-assigned roles: a role is a name + a set of permission strings. No order, no
  inheritance. A user can hold several roles; access = union.
- Tables: `access_roles` (bot_id, name, `permissions` JSON) and `access_role_user`. Permissions live in a JSON column instead of their own table: a role holds a handful of strings, and nothing asks "which roles grant X".
- Rebinds `AuthorizesAccess`: owner / developer always; otherwise permission ∈ union of the
  user's roles; `*` wildcard for a seeded "Full admin" role.
- Owner UI:
  - Admin panel → Roles → create / rename / delete; permission checklist grouped by package,
    inline ✅/⬜ toggles, paginated, callback data carries ids (64-byte limit).
  - User profile → "Roles" button via `UserManagementSections::addSection()` — always
    registered (hard dependency on user-management, no fallback user picker).
- Escalation guard: managing roles needs `access.roles.manage` (owner always has it and may
  delegate); a delegate can grant only permissions they hold; nobody edits their own roles;
  nobody touches the owner.
- No data migration from power levels: an app that had admins moves them itself (vshop does
  it in an app migration that runs before essence drops the column).
- user-management's role cycling and admin/moderator counts are removed.

## Phases

- **A — essence (0.17, breaking).** `ResolvesKeyboard` + default, named menus,
  `keyboard.enabled`, `menu` column to string; `AuthorizesAccess` + default,
  `PermissionRegistry`, `DeclaresPermission`; `power`, `Roles`, `$perm` and the bot admins
  screen removed.
- **B — companions in the same wave.** Each package registers names, moves handlers to
  `$permission = '...'` and requires essence `^0.17`. A handler left on `$perm` would become
  public, so none may ship against 0.17 unconverted.
- **C — `tbe-access` package.** Tables, authorizer, owner UI, user-management section.

Ordering with `multi-chat-context.md`: Phase A here lands **before** multi-chat Phase 1, so the
keyboard resolver already exists when chat context arrives.

## Decisions

- Role management is **delegable** via `access.roles.manage`, bounded by the delegate's own permissions (decided 2026-10-11).
- The change is **breaking**: new releases carry no power levels, no `$perm` fallback and no
  migration from them; each app handles its own upgrade (decided 2026-10-12).
