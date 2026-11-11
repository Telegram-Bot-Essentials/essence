@props(['at', 'exact' => false])
{{-- A moment as "3 days ago" with the exact date in the viewer's calendar on
     hover; exact shows the date itself. A dash when there is none. --}}
@php($calendar = \TelegramBotEssentials\Essence\Web\PanelCalendar::class)
@if ($at === null)
    <span {{ $attributes }}>—</span>
@elseif ($exact)
    <span {{ $attributes }}>{{ $calendar::format($at) }}</span>
@else
    <time datetime="{{ \Illuminate\Support\Carbon::parse($at)->toIso8601String() }}" title="{{ $calendar::format($at) }}" {{ $attributes->style(['opacity:.7;']) }}>{{ \Illuminate\Support\Carbon::parse($at)->diffForHumans() }}</time>
@endif
