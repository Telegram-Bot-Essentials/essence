@extends('tbe-essence::web._shell')
@section('body')
    <p>{{ __('tbe-essence::web.open_from_bot') }}</p>
    @php($login = 'filament.'.\TelegramBotEssentials\Essence\Web\Filament\WebPanelFactory::ID.'.auth.login')
    @if (\Illuminate\Support\Facades\Route::has($login) && auth(\TelegramBotEssentials\Essence\Web\WebAccounts::guard())->guest())
        <p><a href="{{ route($login) }}">{{ __('tbe-essence::web.password_sign_in') }}</a></p>
    @endif
@endsection
