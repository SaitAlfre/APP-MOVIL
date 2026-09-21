@props(['padding' => ''])

<div {{ $attributes->class(['rounded-2xl border border-eh-border bg-eh-surface shadow-sm', $padding]) }}>
    {{ $slot }}
</div>
