@php
    $telegramUser = \TelegramBotEssentials\Essence\Web\Filament\PanelActor::current()?->telegramUser;
@endphp
@if ($telegramUser)
    <span class="fi-tbe-acting-as" style="font-size: .875rem; opacity: .75;">
        {{ __('tbe-essence::web.acting_as', ['name' => $telegramUser->username ? '@'.$telegramUser->username : ($telegramUser->first_name ?: $telegramUser->peer_id)]) }}
    </span>
@endif
