<?php

namespace TelegramBotEssentials\Essence\Web\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\LoginLinks;
use TelegramBotEssentials\Essence\Web\TelegramInitData;

/** Framework-neutral sign-in: any frontend an app picks starts from these routes. */
class WebAuthController extends Controller
{
    private const PANELS = ['manage', 'customer'];

    public function open(Request $request): View
    {
        return view('tbe-essence::web.open', ['panel' => $this->panel($request->query('panel'))]);
    }

    public function telegram(Request $request): JsonResponse
    {
        $bot = $this->bot();
        $token = $bot->getAttribute('bot_token');
        $fields = TelegramInitData::verify(
            $request->string('init_data')->toString(),
            is_string($token) ? $token : '',
            config()->integer('tbe-essence.web.init_data_max_age', 86400),
        );

        $telegramUser = $fields['user'] ?? null;
        $peerId = is_array($telegramUser) ? ($telegramUser['id'] ?? null) : null;
        if (! is_int($peerId)) {
            return response()->json(['message' => (string) __('tbe-essence::web.invalid_telegram_data')], 403);
        }

        $user = BotUser::query()->where('bot_id', $bot->getKey())->where('telegram_user_peer_id', $peerId)->first();
        if (! $user instanceof BotUser) {
            return response()->json(['message' => (string) __('tbe-essence::web.start_the_bot_first')], 403);
        }

        $this->signIn($request, $user->getKey());

        return response()->json(['redirect' => $this->home($this->panel($request->input('panel')), $bot)]);
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

        return redirect()->to($this->home($link['panel'], $this->bot()));
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

    private function home(string $panel, Bot $bot): string
    {
        return url(config()->string("tbe-essence.web.panels.$panel.path", $panel).'/'.$bot->unique_id);
    }

    private function panel(mixed $panel): string
    {
        return is_string($panel) && in_array($panel, self::PANELS, true) ? $panel : 'customer';
    }

    private function bot(): Bot
    {
        $bot = tenancy()->tenant;
        abort_unless($bot instanceof Bot, 404);

        return $bot;
    }
}
