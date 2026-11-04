@extends('tbe-essence::web._shell')
@push('head')
    <script>
        // Only inside Telegram: a browser never waits on telegram.org.
        if (/tgWebApp/.test(location.hash) || sessionStorage.getItem('tbe.tg')) {
            sessionStorage.setItem('tbe.tg', '1');
            document.write('<script src="https://telegram.org/js/telegram-web-app.js"><\/script>');
        }
    </script>
@endpush
@section('body')
    <p data-tbe-signin data-url="{{ route('tbe.web.telegram', ['bot' => request()->route('bot')]) }}" data-panel="{{ $panel }}" data-failed="{{ __('tbe-essence::web.signin_failed') }}">{{ __('tbe-essence::web.open_from_bot') }}</p>
    <script src="{{ route('tbe.web.sign-in-script') }}"></script>
@endsection
