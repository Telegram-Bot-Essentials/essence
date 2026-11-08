<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Web\LoginLinks;
use TelegramBotEssentials\Essence\Web\TelegramAccount;
use TelegramBotEssentials\Essence\Web\TelegramLink;
use TelegramBotEssentials\Essence\Web\WebAccounts;

/** Sign-in by the one-time link /panel sends; any frontend an app picks starts here. */
class WebAuthController extends Controller
{
    public function open(): View
    {
        return view('tbe-essence::web.open');
    }

    public function showLogin(string $bot, string $token, LoginLinks $links): View
    {
        $link = $links->peek($token);
        abort_if($link === null, 410);

        return view('tbe-essence::web.login', [
            'token' => $token,
            'bot' => $bot,
            'linked' => TelegramLink::accountFor($this->peerOf($link['user'])) !== null,
        ]);
    }

    /** POST on purpose: a link preview fetching the GET must not spend the token. */
    public function login(Request $request, string $bot, string $token, LoginLinks $links): RedirectResponse
    {
        $link = $links->consume($token);
        abort_if($link === null || $link['bot'] != $this->bot()->getKey(), 410);

        $account = $this->accountFor($this->peerOf($link['user']));
        Auth::guard(WebAccounts::guard())->login($account);
        $request->session()->regenerate();

        return redirect()->to($this->home($this->bot()));
    }

    /** The account linked to $peerId, or a new one the app creates for it. */
    private function accountFor(int $peerId): TelegramAccount
    {
        return TelegramLink::accountFor($peerId) ?? DB::transaction(function () use ($peerId): TelegramAccount {
            $telegramUser = TelegramUser::query()->where('peer_id', $peerId)->firstOrFail();
            $account = WebAccounts::model()::createForTelegram($telegramUser);
            $account->linkTelegram($peerId);

            return $account;
        });
    }

    private function peerOf(int|string $botUserId): int
    {
        $user = BotUser::query()->find($botUserId);
        abort_unless($user instanceof BotUser, 410);

        return $user->peerId();
    }

    private function home(Bot $bot): string
    {
        return url(config()->string('tbe-essence.web.path', 'manage').'/'.$bot->unique_id);
    }

    private function bot(): Bot
    {
        $bot = tenancy()->tenant;
        abort_unless($bot instanceof Bot, 404);

        return $bot;
    }
}
