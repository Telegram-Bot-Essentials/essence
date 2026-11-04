<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use TelegramBotEssentials\Essence\Models\Bot;

/** Signed-out visitors go to the bot's own sign-in page, not a password form. */
class PanelAuthenticate extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        $bot = $request->route()?->parameter('tenant');
        $bot = $bot instanceof Bot ? $bot->unique_id : $bot;

        return is_string($bot) ? route('tbe.web.open', [
            'bot' => $bot,
            'panel' => Filament::getCurrentOrDefaultPanel()?->getId(),
        ]) : null;
    }
}
