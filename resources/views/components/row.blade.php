{{-- One line of a list inside a section: its parts spread across the line,
     wrapping on narrow screens, a hairline under it. --}}
<div {{ $attributes->class(['fi-tbe-row'])->style(['display:flex;flex-wrap:wrap;gap:.25rem 1rem;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid rgb(127 127 127 / .15);']) }}>
    {{ $slot }}
</div>
