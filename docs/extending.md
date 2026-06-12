# Extending

How to add bot functionality in a Laravel app using this package.

## Directory Structure

Create these directories in your app (the webhook controller auto-loads them):

```
app/Telegram/
├── CallbackQueries/Admin/     # Inline button handlers (admin)
├── CallbackQueries/Member/    # Inline button handlers (member)
├── StateAnswers/Admin/        # Text input handlers (admin)
├── StateAnswers/Member/       # Text input handlers (member)
├── ReplyKeys/Admin/           # Keyboard buttons (admin)
├── ReplyKeys/Member/          # Keyboard buttons (member)
├── Features/Admin/            # Message builders
├── Features/Member/           # Message builders
└── Commands/                  # Slash commands
```

## Generators

```bash
php artisan make:callback-query Admin/ProductsQuery
php artisan make:state-answer Admin/ProductsAnswer
php artisan make:reply-key Member/BuyServiceKey
php artisan make:feature Admin/ProductsFeature
php artisan make:command StartCommand
```

Each generator creates a stub in the matching `app/Telegram/` subdirectory.

## Typical Feature Flow

A complete admin CRUD menu usually involves four class types:

```
Feature (UI)  ←── builds TelegramResponse with inline buttons
     │
     ▼
CallbackQuery ── handles button presses, may start input flows
     │
     ▼
StateAnswer  ── handles text input while user.state is set
     │
     ▼
Feature (UI)  ←── show result, return to menu
```

### 1. Feature — build the UI

```php
class ProductsFeature
{
    static string $type = 'PRODUCTS';

    public static function menu(): TelegramResponse
    {
        $replyMarkup = Keyboard::make()->inline();
        $replyMarkup->row([
            Keyboard::inlineButton([
                'text'          => 'Add Product',
                'callback_data' => encodeCallback(self::$type, 'add'),
            ]),
        ]);

        return new TelegramResponse(
            text: 'Product Management',
            replyMarkup: $replyMarkup,
        );
    }
}
```

Use the same `$type` string in the corresponding CallbackQuery class.

### 2. CallbackQuery — handle button presses

```php
class ProductsQuery extends CallbackQuery
{
    protected string $type = 'PRODUCTS';
    protected int $perm = Roles::ADMIN->value;

    public function add(): void
    {
        $stateData = stateData()->store(['flow' => 'add_product']);

        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction();

        wHook()->user()->changeState(encodeAnswerState($this->type, 'add', [
            'target_field' => 'title',
            'state_data'   => $stateData->id,
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id'      => wHook()->user()->telegramUser->peer_id,
            'text'         => 'Enter product title:',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    public function show(Product $product): void
    {
        ProductsFeature::show($product)->update();
    }
}
```

Notes:
- `$type` must match `encodeCallback()` / `encodeAnswerState()` first segment
- Eloquent model params resolve by **position** in callback data
- Use `->update()` to edit the current inline message, `->send()` for a new one

### 3. StateAnswer — handle text input

```php
class ProductsAnswer extends StateAnswer
{
    protected string $type = 'PRODUCTS';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [AllowableFields::TEXT->value];

    public function add(): void
    {
        $field = $this->params['target_field'];
        $value = wHook()->update()->message->text;

        stateData()->addData($this->stateData(), [$field => $value]);

        $fields = ['title', 'url', 'price'];
        if ($field !== collect($fields)->last()) {
            $next = getNextFromArray($fields, $field);
            wHook()->user()->addParamToState(['target_field' => $next]);
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text'    => "Enter {$next}:",
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);
            return;
        }

        Product::create($this->stateData()->data);
        $this->stateData()->delete();
        wHook()->user()->changeState();
        $this->messageMeta()->delete();

        ProductsFeature::menu()->send();
    }

    public function cancel(): void
    {
        $this->messageMeta()?->revertAction();
        $this->stateData()?->delete();
    }
}
```

Notes:
- `$type` must match the CallbackQuery that started the flow
- `$allowedFields` controls which message types trigger the handler
- Implement `cancel()` for cleanup when the user cancels or starts a new flow
- `$this->params` contains named state params; `$this->stateData()` loads the JSON row

### 4. ReplyKey — keyboard navigation

Register in `config/tbe-essence.php`:

```php
'keyboard' => [
    'admin' => [
        [ProductsKey::class],
    ],
    'member' => [
        [BuyServiceKey::class],
    ],
],
```

```php
class ProductsKey extends ReplyKey
{
    protected string $text = 'Products';
    protected int $perm = Roles::ADMIN->value;

    public function handle(): void
    {
        ProductsFeature::menu()->send();
    }
}
```

## Registering Classes

### Auto-discovery (recommended)

Place files under `app/Telegram/CallbackQueries/` and `app/Telegram/StateAnswers/`. The webhook controller scans these on every request:

```php
loadCallbackQueries(base_path('app/Telegram/CallbackQueries/Admin'));
loadStateAnswers(base_path('app/Telegram/StateAnswers/Member'));
```

### Manual registration (for packages)

In a service provider's `boot()`:

```php
callbackQueryBus()->addCallbackQueries([
    MyPackageQuery::class,
]);

stateAnswerBus()->addStateAnswers([
    MyPackageAnswer::class,
]);

replyKeyBus()->addReplyKey(MyPackageKey::class);
```

## Callback vs State Encoding

| | CallbackQueries | StateAnswers |
|--|----------------|--------------|
| Encoding | `encodeCallback()` | `encodeAnswerState()` |
| Params | Positional (`?42&7`) | Named (`?product_id=42`) |
| Size limit | 64 bytes (Telegram) | None (database) |
| Trigger | Inline button press | Text while state is set |

When callback data would exceed 64 bytes, store the payload in StateData and pass only the ID:

```php
// Too large for callback_data
encodeCallback('MYTYPE', 'action', [$stateData->id]);  // just the ID
```

## MessageMeta in Flows

Lock the originating message when starting input:

```php
$messageMeta = MessageMeta::makeWithCurrentMessage();
$messageMeta->lockAction('Waiting for input...');
```

Pass `message_meta` or `message_meta_id` in state params so the StateAnswer can clean up:

```php
$this->messageMeta()->delete();              // remove lock message
$this->messageMeta()->revertAction();        // restore original (cancel)
$this->messageMeta()->updateAndContinueAction($featureResponse);  // show result
```

## Commands

Register in `config/tbe-essence.php`:

```php
'commands' => [
    StartCommand::class,
    HelpCommand::class,
],
```

Commands extend `Telegram\Bot\Commands\Command`. Use `$this->pattern` for arguments:

```php
protected string $pattern = '{stateData}';

public function handle()
{
    wHook()->user()->update(['state' => null]);  // clear state on /start
    // ...
}
```

## Inline Confirmations

For destructive actions, use the built-in confirmation helper:

```php
Keyboard::inlineButton([
    'text' => 'Delete',
    'callback_data' => inlineConfirmationKey(
        keyText: 'Delete',
        targetCallbackData: encodeCallback('PRODUCTS', 'delete', [$product->id]),
        backCallbackData: encodeCallback('PRODUCTS', 'show', [$product->id]),
        confirmationText: 'Are you sure?',
    ),
]);
```

This creates an `InlineConfirmation` record and routes through `InlineConfirmationFeature`.

## Dependency Injection in Handlers

Both CallbackQuery and StateAnswer resolve method parameters automatically:

- **Eloquent models** — loaded by ID from params (position for callbacks, name for state answers)
- **Classes** — resolved from the container
- **Scalars** — from params

Custom column binding (StateAnswer only):

```php
protected array $bindings = [
    Product::class => 'product_id',
];
```

## Package Extension Example

For a companion package (billing, wallet, etc.):

```php
// MyPackageServiceProvider.php
public function boot(): void
{
    callbackQueryBus()->addCallbackQuery(ManageInvoicesQuery::class);
    stateAnswerBus()->addStateAnswer(ManageInvoicesAnswer::class);
}
```

Place package defaults under `src/Telegram/CallbackQueries/` — they load automatically alongside app classes.
