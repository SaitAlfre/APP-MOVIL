@props(['padding' => ''])

<div {{ $attributes->class(['ui-card min-w-0 rounded-2xl border border-eh-border bg-eh-surface shadow-sm', $padding]) }}>
    {{ $slot }}
</div>
