{{-- Label/value pairs in a grid that wraps on narrow screens. Fill it with
     <x-tbe::fact>s. --}}
<dl {{ $attributes->class(['fi-tbe-facts'])->style(['display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem;margin:0;']) }}>
    {{ $slot }}
</dl>
