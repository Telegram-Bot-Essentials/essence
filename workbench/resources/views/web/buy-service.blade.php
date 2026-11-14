<x-filament-panels::page>
    {{ $this->table }}
    @if ($paidContext)
        <x-filament::section heading="Context on the update request" compact>{{ $paidContext }}</x-filament::section>
    @endif
</x-filament-panels::page>
