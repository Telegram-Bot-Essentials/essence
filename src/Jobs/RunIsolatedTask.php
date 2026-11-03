<?php

namespace TelegramBotEssentials\Essence\Jobs;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Laravel\SerializableClosure\SerializableClosure;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Services\IsolatedTasks;
use Throwable;

/**
 * The one job behind background(): a closure, and who it runs for. Only ids
 * travel, so the payload holds no token and the models it needs are fresh.
 */
class RunIsolatedTask implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    private SerializableClosure $work;

    /**
     * @param  list<int>  $backoff
     */
    private function __construct(
        public readonly string $name,
        Closure $work,
        private readonly ?int $botId,
        private readonly ?int $botUserId,
        private readonly ?int $chatId,
        private readonly ?int $threadId,
        int $tries,
        array $backoff,
    ) {
        $this->work = new SerializableClosure($work);
        $this->tries = $tries;
        $this->backoff = $backoff;
    }

    /**
     * Who the task runs for is fixed now, while the webhook still knows: the
     * given user in their private chat, or else whoever asked, where they asked.
     *
     * @param  list<int>  $backoff
     */
    public static function capture(string $name, Closure $work, ?BotUser $for, int $tries, array $backoff): self
    {
        $live = wHook()->check();
        $user = $for ?? ($live ? wHook()->user() : null);
        $conversation = $for === null && $live ? wHook()->conversation() : null;

        $botId = $user?->getAttribute('bot_id') ?? (wHook()->hasBot() ? wHook()->bot()->getKey() : null);
        $chatId = $conversation?->getAttribute('chat_id');
        $threadId = $conversation?->getAttribute('thread_id');

        return new self(
            $name,
            $work,
            is_numeric($botId) ? (int) $botId : null,
            is_numeric($user?->getKey()) ? (int) $user->getKey() : null,
            is_numeric($chatId) ? (int) $chatId : null,
            is_numeric($threadId) ? (int) $threadId : null,
            $tries,
            $backoff,
        );
    }

    public function displayName(): string
    {
        return 'task: '.$this->name;
    }

    public function handle(): void
    {
        $work = $this->work->getClosure();

        $bot = $this->botId === null ? null : Bot::query()->find($this->botId);

        if ($bot === null) {
            $work();

            return;
        }

        tenancy()->initialize($bot);

        $user = $this->botUserId === null ? null : BotUser::query()->find($this->botUserId);

        $locale = App::getLocale();
        App::setLocale(app(ResolvesBotLocale::class)->resolve($bot));

        try {
            wHook()->runInContext($bot, $user, $this->chatId, $this->threadId, $work);
        } finally {
            App::setLocale($locale);
        }
    }

    public function failed(Throwable $e): void
    {
        app(IsolatedTasks::class)->report($this->name, $e);

        // Retries mean it had to arrive, and it has given up: someone should know.
        if ($this->tries > 1) {
            exceptionReport($e);
        }
    }
}
