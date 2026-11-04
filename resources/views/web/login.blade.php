@extends('tbe-essence::web._shell')
@section('body')
    <form method="post" action="{{ route('tbe.web.login', ['bot' => $bot, 'token' => $token]) }}">
        @csrf
        <p>{{ __('tbe-essence::web.login_hint') }}</p>
        <button type="submit">{{ __('tbe-essence::web.login_confirm') }}</button>
    </form>
@endsection
