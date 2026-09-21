@props([
    'name' => 'q',
    'id' => null,
    'label' => 'Buscar',
    'placeholder' => 'Buscar…',
    'value' => null,
])

@php $campoId = $id ?? $name; @endphp

<div class="relative">
    <label for="{{ $campoId }}" class="sr-only">{{ $label }}</label>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-eh-text-muted">
        <x-icon name="search" class="h-4 w-4" />
    </span>
    <input
        id="{{ $campoId }}"
        name="{{ $name }}"
        type="search"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class('w-full rounded-xl border border-eh-border bg-eh-surface py-2 pl-9 pr-3 text-sm text-eh-text transition-colors focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary') }}>
</div>
