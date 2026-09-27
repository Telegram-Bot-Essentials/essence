<?php

namespace TelegramBotEssentials\Essence\Services;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\TenantScope;
use Telegram\Bot\Api;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * Tells a bot's owner and admins about something only they can fix: a
 * misconfigured setting, a permission the bot lost, an order that could not
 * be delivered. Not for bugs - those go to the developer via exceptionReport().
 *
 * Alerts are throttled per bot and key, so a condition detected on every
 * update reaches each admin once per window rather than once per update.
 */
class AdminAlerts
{
    /**
     * @param  string  $key  what the alert is about, e.g. "settings.channel_lock.bot_not_admin"
     * @param  Closure(): string|string  $text  a Closure is rendered in the bot's locale
     * @param  Bot|null  $bot  the current webhook's bot when null
     * @param  int|null  $throttle  seconds before the same key alerts again
     * @return bool whether the alert went out (false when throttled or nobody to tell)
     */
    public function send(string $key, Closure|string $text, ?Bot $bot = null, ?int $throttle = null): bool
    {
        try {
            return $this->deliver($key, $text, $bot, $throttle);
        } catch (Throwable $e) {
            // Alerting is a side channel: it must not break the flow that raised it.
            tbeLog('essence')->error('Admin alert "{alert}" failed: '.$e->getMessage(), ['alert' => $key, 'exception' => $e]);

            return false;
        }
    }

    private function deliver(string $key, Closure|string $text, ?Bot $bot, ?int $throttle): bool
    {
        $bot ??= wHook()->check() ? wHook()->bot() : null;
        if ($bot === null) {
            tbeLog('essence')->warning('Admin alert "{alert}" dropped: no bot to alert', ['alert' => $key]);

            return false;
        }

        if (! Cache::add('tbe:admin-alert:'.$bot->id.':'.$key, true, $throttle ?? $this->defaultThrottle())) {
            return false;
        }

        $recipients = $this->recipients($bot);
        $text = '⚠️ '.$this->render($bot, $text);
        $api = $this->api($bot);

        $delivered = 0;
        foreach ($recipients as $peerId) {
            try {
                $api->sendMessage(['chat_id' => $peerId, 'text' => $text]);
                $delivered++;
            } catch (Throwable $e) {
                tbeLog('essence')->warning('Admin alert "{alert}" not delivered to {recipient}: '.$e->getMessage(), [
                    'bot_id' => $bot->id,
                    'alert' => $key,
                    'recipient' => $peerId,
                ]);
            }
        }

        tbeLog('essence')->warning('Admin alert "{alert}" sent to {delivered}/{recipients} admin(s)', [
            'bot_id' => $bot->id,
            'alert' => $key,
            'delivered' => $delivered,
            'recipients' => count($recipients),
            'text' => $text,
        ]);

        return $delivered > 0;
    }

    private function defaultThrottle(): int
    {
        $throttle = config('tbe-essence.admin_alerts.throttle');

        return is_numeric($throttle) ? (int) $throttle : 21600;
    }

    /**
     * The owner plus every admin still reachable and not suspended.
     *
     * @return list<int>
     */
    private function recipients(Bot $bot): array
    {
        /** @var list<int|string> $admins */
        $admins = BotUser::withoutGlobalScope(TenantScope::class)
            ->where('bot_id', $bot->id)
            ->where('power', '>=', Roles::ADMIN->value)
            ->reachable()
            ->notSuspended()
            ->pluck('telegram_user_peer_id')
            ->all();

        $owner = $bot->getAttribute('bot_owner_peer_id');
        $peerIds = array_map(intval(...), [is_numeric($owner) ? $owner : 0, ...$admins]);

        return array_values(array_unique(array_filter($peerIds)));
    }

    private function render(Bot $bot, Closure|string $text): string
    {
        if (is_string($text)) {
            return $text;
        }

        $original = App::getLocale();
        App::setLocale(app(ResolvesBotLocale::class)->resolve($bot));

        try {
            $rendered = $text();

            return is_string($rendered) ? $rendered : '';
        } finally {
            App::setLocale($original);
        }
    }

    /** The webhook's client when alerting its own bot, a fresh one otherwise (a job, the scheduler). */
    private function api(Bot $bot): Api
    {
        if (wHook()->check() && wHook()->bot()->is($bot)) {
            return wHook()->api();
        }

        $token = $bot->getAttribute('bot_token');

        return telegramApi(is_string($token) ? $token : '');
    }
}
