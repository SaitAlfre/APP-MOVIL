@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'unit' => null,
])

@php
    $campoId = $id ?? $name;
    $mensaje = $error ?? ($name ? $errors->first($name) : null);
    $valor = old($name, $value);
@endphp

<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $campoId }}" class="text-[11px] font-semibold text-eh-text">
            {{ $label }}
            @if ($required)
                <span class="text-eh-red" aria-hidden="true">*</span>
                <span class="sr-only">(obligatorio)</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $campoId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $valor }}"
            @if ($required) required @endif
            @if ($mensaje) aria-invalid="true" aria-describedby="{{ $campoId }}-error" @elseif ($hint) aria-describedby="{{ $campoId }}-hint" @endif
            {{ $attributes->class([
                'w-full rounded-xl border bg-eh-surface px-3.5 py-2.5 text-[13px] text-eh-text transition focus:outline-none focus:ring-[3px]',
                'border-eh-red focus:border-eh-red focus:ring-eh-red/15' => (bool) $mensaje,
                'border-eh-border-strong focus:border-eh-sage focus:ring-eh-sage/15' => ! $mensaje,
                'pr-12' => (bool) $unit,
            ]) }}>
        @if ($unit)
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-eh-text-muted">{{ $unit }}</span>
        @endif
    </div>

    @if ($mensaje)
        <p id="{{ $campoId }}-error" class="text-xs text-eh-red">{{ $mensaje }}</p>
    @elseif ($hint)
        <p id="{{ $campoId }}-hint" class="text-xs text-eh-text-muted">{{ $hint }}</p>
    @endif
</div>
