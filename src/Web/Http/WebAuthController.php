<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Web\LoginLinks;

/** Sign-in by the one-time link /panel sends; any frontend an app picks starts here. */
class WebAuthController extends Controller
{
    public function open(): View
    {
        return view('tbe-essence::web.open');
    }

    public function showLogin(string $bot, string $token, LoginLinks $links): View
    {
        abort_if($links->peek($token) === null, 410);

        return view('tbe-essence::web.login', ['token' => $token, 'bot' => $bot]);
    }

    /** POST on purpose: a link preview fetching the GET must not spend the token. */
    public function login(Request $request, string $bot, string $token, LoginLinks $links): RedirectResponse
    {
        $link = $links->consume($token);
        abort_if($link === null || $link['bot'] != $this->bot()->getKey(), 410);

        $this->signIn($request, $link['user']);

        return redirect()->to($this->home($this->bot()));
    }

    private function signIn(Request $request, mixed $userId): void
    {
        /** @var class-string<Model&Authenticatable> $model */
        $model = config()->string('auth.providers.tbe-web-users.model');

        $user = $model::query()->findOrFail($userId);
        abort_unless($user instanceof Authenticatable, 404);

        Auth::guard('tbe-web')->login($user);
        $request->session()->regenerate();
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
