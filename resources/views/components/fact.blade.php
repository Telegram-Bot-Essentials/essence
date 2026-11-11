@props(['label', 'value' => null, 'wide' => false])
{{-- One label/value pair inside <x-tbe::facts>; the slot wins over value,
     and a wide one spans the whole row. --}}
<div {{ $attributes->class(['fi-tbe-fact'])->style(['grid-column:1/-1;' => $wide]) }}>
    <dt style="font-size:.875rem;opacity:.7;">{{ $label }}</dt>
    <dd style="margin:0;font-weight:600;overflow-wrap:anywhere;">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ filled($value) ? $value : '—' }}
        @endif
    </dd>
</div>
