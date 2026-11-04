<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Gives a panel request what bot code expects: the tenant, plus wHook()'s bot,
 * api client and user. Runs on Livewire's update requests too (registered as
 * persistent), which carry no bot in their own URL.
 */
class BindWebContext
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $bot = Filament::getTenant();
        abort_unless($bot instanceof Bot, 404);

        tenancy()->initialize($bot);

        $hook = wHook();
        $token = $bot->getAttribute('bot_token');
        $hook->setBot($bot);
        $hook->setApi(telegramApi(is_string($token) ? $token : ''));
        $hook->setUpdate(new Update([]));

        $user = Filament::auth()->user();
        if ($user instanceof BotUser) {
            $hook->setUser($user);
        }

        return $next($request);
    }
}
