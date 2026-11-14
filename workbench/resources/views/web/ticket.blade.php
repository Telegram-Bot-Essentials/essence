<x-filament-panels::page>
    @foreach ($messages as $i => $message)
        <x-filament::section :heading="$message['author']" :description="$message['time']" :icon="$message['staff'] ? 'heroicon-o-lifebuoy' : 'heroicon-o-user'" compact wire:key="m{{ $i }}">
            {{ $message['text'] }}
        </x-filament::section>
    @endforeach
    <form wire:submit="send">
        {{ $this->form }}
        <div style="margin-block-start: 1rem">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">Send</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
