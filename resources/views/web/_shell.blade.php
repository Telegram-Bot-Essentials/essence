<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; font-family: system-ui, sans-serif; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; box-sizing: border-box; text-align: center; }
        button { padding: 10px 20px; font: inherit; cursor: pointer; }
    </style>
    @stack('head')
</head>
<body>@yield('body')</body>
</html>
