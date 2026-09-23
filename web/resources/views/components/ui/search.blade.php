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
        {{ $attributes->class('w-full rounded-xl border border-eh-border-strong bg-eh-surface/65 py-2.5 pl-9 pr-3 text-[13px] text-eh-text transition hover:border-eh-text/25 focus:border-eh-sage focus:bg-eh-surface focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15') }}>
</div>
