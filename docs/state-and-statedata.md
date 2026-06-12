# State & StateData

Two separate mechanisms work together for multi-step input flows. They are **not** the same thing.

| | `bot_users.state` | `StateData` |
|--|-------------------|-------------|
| **Answers** | "Which handler runs on the next message?" | "What data have we collected?" |
| **Storage** | Column on `bot_users` | Row in `state_data` table |
| **Format** | Encoded string | JSON object |
| **Per user** | One active state at a time | Independent rows, referenced by ID |
| **Auto-cleared** | Yes (`changeState(null)`) | No — manual delete required |
| **Used in deeplinks** | No | Yes (`/start={id}`) |

## bot_users.state

### Format

```
PRODUCTSMANAGEMENT#add?target_field=title&state_data=7&message_meta=42
```

Built with `encodeAnswerState($type, $method, $params)`:

```php
wHook()->user()->changeState(encodeAnswerState('PRODUCTSMANAGEMENT', 'add', [
    'target_field' => 'title',
    'state_data'   => 7,
    'message_meta' => 42,
]));
```

Decoded by `decodeAnswerState()` into:

```php
[
    'type'   => 'PRODUCTSMANAGEMENT',
    'method' => 'add',
    'params' => ['target_field' => 'title', 'state_data' => '7', ...],
]
```

### Lifecycle

| Action | Method | When |
|--------|--------|------|
| Enter input mode | `changeState($encoded)` | CallbackQuery starts a flow |
| Update routing params | `addParamToState(['key' => 'val'])` | Move to next step/field |
| Exit input mode | `changeState()` or `changeState(null)` | Flow complete or cancelled |

While state is set:
- Normal keyboard is replaced with **Cancel Process**
- Incoming text messages route to `StateAnswerBus` instead of ReplyKeys
- Commands cancel the active state first

Source: `src/Models/BotUser.php`

### What belongs in state params

Keep these **small and scalar**:

- Handler routing: `target_field`, `step`
- Model IDs: `product_id`, `order_id`, `message_meta_id`
- StateData pointer: `state_data` or `state_data_id`

Do **not** put collected form values or large payloads here.

## StateData

### Schema

```sql
state_data
├── id (bigint)
└── data (json, nullable)
```

No timestamps. Treat rows as ephemeral session data.

Source: `database/migrations/2025_07_23_191544_create_state_data_table.php`

### API

```php
// Create
$row = stateData()->store(['flow' => 'add_product', 'type' => 'tbe']);

// Append (merges, does not replace)
stateData()->addData($row, ['title' => 'My Product']);

// Read
$row->data['title'];

// Cleanup (your responsibility)
$row->delete();
```

Source: `src/Services/StateDataService.php`, `src/Models/StateData.php`

### Access from StateAnswer

```php
$this->stateData()->data['title'];
```

Loads the row whose ID is in `$this->params['state_data']` or `$this->params['state_data_id']`.

Source: `src/Telegram/StateAnswers/StateAnswer.php`

## Patterns

### Pattern 1: State only (single-field edit)

No StateData needed. Pass the model ID directly in state params:

```php
// CallbackQuery
wHook()->user()->changeState(encodeAnswerState($this->type, 'edit', [
    'product_id'      => $product->id,
    'target_field'    => $targetField,
    'message_meta_id' => $messageMeta->id,
]));

// StateAnswer
public function edit(Product $product, string $targetField): void
{
    $value = wHook()->update()->message->text;
    $product->$targetField = $value;
    $product->save();
    wHook()->user()->changeState();
}
```

### Pattern 2: State + StateData (multi-step wizard)

**Start (CallbackQuery):**

```php
$stateData = stateData()->store(['type' => $type]);

wHook()->user()->changeState(encodeAnswerState($this->type, 'add', [
    'target_field' => 'title',
    'state_data'   => $stateData->id,
    'message_meta' => $messageMeta->id,
]));
```

**Each step (StateAnswer):**

```php
public function add(): void
{
    $field = $this->params['target_field'];
    $value = wHook()->update()->message->text;

    stateData()->addData($this->stateData(), [$field => $value]);

    if ($field !== 'last_field') {
        wHook()->user()->addParamToState(['target_field' => $nextField]);
        // prompt next question...
        return;
    }

    // Finalize from $this->stateData()->data
    Product::create([...]);
    $this->stateData()->delete();
    wHook()->user()->changeState();
    $this->messageMeta()->delete();
}
```

Rules:
- **One StateData row** per wizard — don't create a new row per step
- **Advance steps** with `addParamToState()` — only the pointer in state changes
- **Delete** the row when done

### Pattern 3: Mid-flow StateData creation

Create StateData when the first step produces a value needed later:

```php
case 'telegram_id':
    $tgUser = TelegramUser::where('peer_id', $value)->firstOrFail();
    $stateData = stateData()->store(['telegram_id' => $tgUser->peer_id]);
    $params['target_field'] = 'username';
    $params['state_data'] = $stateData->id;
    wHook()->user()->changeState(encodeAnswerState($this->type, 'add', $params));
    break;

case 'username':
    $telegramId = $this->stateData()->data['telegram_id'];
    // ...
```

### Pattern 4: Deeplink / cross-app payload

StateData without setting `bot_users.state`:

```php
// Web app
$stateData = StateData::create([
    'data' => ['type' => 'connect', 'token' => $token],
]);
return redirect("https://t.me/{$botUsername}?start={$stateData->id}");
```

```php
// StartCommand
$stateData = StateData::findOrFail($argument);
switch ($stateData->data['type']) {
    case 'connect': /* ... */ break;
}
```

Pass `$stateData->id` into callback buttons instead of the token (stays under 64-byte callback limit).

## Decision Checklist

```
Collecting 2+ text inputs in sequence?
  → StateData + bot_users.state with state_data=ID

Passing payload from web/external system?
  → StateData + /start={id} or callback with ID

Single field edit on existing model?
  → bot_users.state only (model ID in params)

One-shot input, save immediately?
  → bot_users.state only, no StateData
```

## Cleanup

`changeState(null)` does **not** delete StateData rows. Always delete manually:

```php
$this->stateData()?->delete();
```

Consider a scheduled job to prune orphaned rows. The table has no `created_at` column by default — add one via migration if you need TTL-based cleanup.

## Recommended JSON Shape

For new flows, use a consistent structure:

```php
[
    'flow'    => 'add_product',     // dispatch key
    'variant' => 'tbe',             // business subtype
    // flat collected fields, or:
    'fields'  => ['title' => '...'], // nested user input
]
```

This keeps dispatch logic separate from collected values.
