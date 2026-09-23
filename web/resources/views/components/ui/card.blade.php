@props(['padding' => ''])

<div {{ $attributes->class(['rounded-[22px] border border-eh-border bg-eh-surface/70', $padding]) }}>
    {{ $slot }}
</div>
