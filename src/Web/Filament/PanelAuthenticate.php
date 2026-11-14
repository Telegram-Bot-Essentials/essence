<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Http\Middleware\Authenticate;
use TelegramBotEssentials\Essence\Models\Bot;

/**
 * Signed-out visitors to a bot's pages go to that bot's sign-in page, which
 * offers /panel and the password form; anywhere else, to the password form.
 */
class PanelAuthenticate extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        $bot = $request->route()?->parameter('tenant');
        $bot = $bot instanceof Bot ? $bot->unique_id : $bot;

        return is_string($bot) ? route('tbe.web.open', ['bot' => $bot]) : parent::redirectTo($request);
    }
}
