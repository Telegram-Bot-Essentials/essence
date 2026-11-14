@props([
    'label',
    'value' => null,
    'icon' => null,
    'url' => null,
    'description' => null,
])
{{-- A figure on its own card: icon and label above, the value large. Names
     (a list value) show as badges. The whole card links when given a url. --}}
@if ($url !== null)
    <a href="{{ $url }}" {{ $attributes->class(['fi-tbe-stat-card'])->style(['display:block;color:inherit;text-decoration:none;']) }}>
@else
    <div {{ $attributes->class(['fi-tbe-stat-card']) }}>
@endif
    <x-filament::section compact>
        <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;opacity:.7;">
            @if ($icon !== null)
                <x-filament::icon :icon="$icon" style="width:1.25rem;height:1.25rem;" />
            @endif
            <span>{{ $label }}</span>
            @if ($url !== null)
                <x-filament::icon icon="heroicon-m-arrow-up-right" style="width:1rem;height:1rem;margin-inline-start:auto;" />
            @endif
        </div>
        <div style="margin-top:.5rem;font-size:1.5rem;font-weight:600;line-height:1.3;">
            @if (is_array($value))
                <div style="display:flex;flex-wrap:wrap;gap:.375rem;font-size:1rem;">
                    @forelse ($value as $name)
                        <x-filament::badge>{{ $name }}</x-filament::badge>
                    @empty
                        —
                    @endforelse
                </div>
            @elseif ($slot->isNotEmpty())
                {{ $slot }}
            @else
                {{ filled($value) ? $value : '—' }}
            @endif
        </div>
        @if (filled($description))
            <div style="margin-top:.25rem;font-size:.875rem;opacity:.7;">{{ $description }}</div>
        @endif
    </x-filament::section>
@if ($url !== null)
    </a>
@else
    </div>
@endif
